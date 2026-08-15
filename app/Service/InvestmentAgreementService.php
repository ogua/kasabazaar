<?php

namespace App\Service;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class InvestmentAgreementService
{
    public static function generatePdf(Investment $investment, ?Carbon $asOfDate = null): \Barryvdh\DomPDF\PDF
    {
        $investment->load('investor');
        $asOfDate ??= $investment->defaultAsOfDate();
        $isLoan = $investment->capital_type === InvestmentCapitalType::loan;

        return Pdf::loadView('pdf.investment-agreement', [
            'investment' => $investment,
            'investor' => $investment->investor,
            'valuation' => $isLoan ? null : app(InvestmentInterestService::class)->valuationAsOf($investment, $asOfDate),
            'rateHistory' => $isLoan ? [] : self::buildRateHistory($investment, $asOfDate),
            'interestHistory' => $isLoan ? collect() : $investment->transactions()
                ->where('type', InvestmentTransactionType::interest_credit->value)
                ->where('posted', true)
                ->orderBy('date')
                ->get(),
            'payoutSchedule' => $isLoan ? ($payoutSchedule = app(InvestmentInterestPayoutService::class)->projectSchedule($investment)) : [],
            'loanRate' => $isLoan ? ($payoutSchedule[0]['rate'] ?? null) : null,
            'asOfDate' => $asOfDate,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    public static function generateCombinedPdf(Investor $investor, ?Carbon $asOfDate = null): \Barryvdh\DomPDF\PDF
    {
        $asOfDate ??= $investor->defaultAsOfDate();
        $interestService = app(InvestmentInterestService::class);
        $payoutService = app(InvestmentInterestPayoutService::class);

        // Investment tranches (compounding) and loan tranches (fixed schedule) are
        // legally distinct instruments, so each gets its own section below using the
        // wording proven correct in the single-tranche agreement — but both belong in
        // one combined document so an investor holding either or both types always
        // receives a single complete agreement rather than an empty/partial one.
        $investments = $investor->investments()
            ->where('capital_type', InvestmentCapitalType::investment->value)
            ->orderBy('start_date')
            ->get();
        $loanInvestments = $investor->investments()
            ->where('capital_type', InvestmentCapitalType::loan->value)
            ->orderBy('start_date')
            ->get();

        // includeUnposted: false — the agreement must only reflect interest that has
        // actually been credited. Projecting a not-yet-posted period would show the
        // investor a return they have not been paid, especially once $asOfDate is
        // pulled forward by a sibling tranche with no posted interest of its own.
        $segmentedValuations = $investments->mapWithKeys(
            fn (Investment $investment) => [$investment->id => $interestService->segmentedValuationAsOf($investment, $asOfDate, includeUnposted: false)]
        );

        $loanPayoutSchedules = $loanInvestments->mapWithKeys(
            fn (Investment $investment) => [$investment->id => $payoutService->projectSchedule($investment)]
        );
        $loanRates = $loanPayoutSchedules->map(fn (array $schedule) => $schedule[0]['rate'] ?? null);

        // The date actually reflected by the figures above — the latest date any
        // investment tranche has posted interest through — rather than the (possibly
        // later) requested/default $asOfDate. Falls back to $asOfDate for a loan-only
        // investor, who has no compounding valuation to reflect.
        $reflectedAsOfDate = $segmentedValuations->isNotEmpty()
            ? ($segmentedValuations->max(fn (array $valuation) => $valuation['as_of']) ?? $asOfDate)
            : $asOfDate;

        return Pdf::loadView('pdf.investment-agreement-combined', [
            'investor' => $investor,
            'investments' => $investments,
            'loanInvestments' => $loanInvestments,
            'loanPayoutSchedules' => $loanPayoutSchedules,
            'loanRates' => $loanRates,
            'valuations' => $segmentedValuations,
            'totalPrincipal' => $investments->sum('principal_amount'),
            'totalValue' => $segmentedValuations->sum('compounded_balance'),
            'totalInterest' => $segmentedValuations->sum('interest_earned_total'),
            'totalLoanPrincipal' => $loanInvestments->sum('principal_amount'),
            'rateClauses' => self::buildCombinedRateClauses($investments, $asOfDate),
            'asOfDate' => $reflectedAsOfDate,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    public static function downloadPdf(Investment $investment, ?Carbon $asOfDate = null)
    {
        return self::generatePdf($investment, $asOfDate)->download("investment-agreement-{$investment->reference}.pdf");
    }

    public static function streamPdf(Investment $investment, ?Carbon $asOfDate = null)
    {
        return self::generatePdf($investment, $asOfDate)->stream("investment-agreement-{$investment->reference}.pdf");
    }

    public static function downloadCombinedPdf(Investor $investor, ?Carbon $asOfDate = null)
    {
        return self::generateCombinedPdf($investor, $asOfDate)->download("investment-agreement-{$investor->id}.pdf");
    }

    public static function streamCombinedPdf(Investor $investor, ?Carbon $asOfDate = null)
    {
        return self::generateCombinedPdf($investor, $asOfDate)->stream("investment-agreement-{$investor->id}.pdf");
    }

    public static function sendEmail(Investment $investment, ?Carbon $asOfDate = null): bool
    {
        $investment->load('investor');
        $email = $investment->investor?->email;

        if (! $email) {
            return false;
        }

        $pdf = self::generatePdf($investment, $asOfDate);
        $docTitle = $investment->capital_type === InvestmentCapitalType::loan ? 'Loan Agreement' : 'Investment Agreement';

        try {
            Mail::send('emails.investment-agreement', [
                'investment' => $investment,
                'investor' => $investment->investor,
            ], function ($message) use ($email, $investment, $pdf, $docTitle) {
                $message->to($email)
                    ->subject($docTitle.' — '.$investment->reference)
                    ->attachData($pdf->output(), "investment-agreement-{$investment->reference}.pdf", [
                        'mime' => 'application/pdf',
                    ]);
            });

            return true;
        } catch (\Exception $e) {
            logger()->error("Failed to send investment agreement email: {$e->getMessage()}");

            return false;
        }
    }

    public static function sendCombinedEmail(Investor $investor, ?Carbon $asOfDate = null): bool
    {
        if (! $investor->email) {
            return false;
        }

        $pdf = self::generateCombinedPdf($investor, $asOfDate);
        $hasInvestments = $investor->investments->contains(fn (Investment $i) => $i->capital_type === InvestmentCapitalType::investment);
        $hasLoans = $investor->investments->contains(fn (Investment $i) => $i->capital_type === InvestmentCapitalType::loan);
        $docTitle = $hasInvestments && $hasLoans
            ? 'Investment & Loan Agreement'
            : ($hasLoans ? 'Loan Agreement' : 'Investment Agreement');

        try {
            Mail::send('emails.investment-agreement-combined', [
                'investor' => $investor,
                'investments' => $investor->investments,
                'totalPrincipal' => $investor->investments->sum('principal_amount'),
            ], function ($message) use ($investor, $pdf, $docTitle) {
                $message->to($investor->email)
                    ->subject($docTitle.' — '.$investor->name)
                    ->attachData($pdf->output(), "investment-agreement-{$investor->id}.pdf", [
                        'mime' => 'application/pdf',
                    ]);
            });

            return true;
        } catch (\Exception $e) {
            logger()->error("Failed to send combined investment agreement email: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * @return array<int, array{rate: ?float, source: string}>
     */
    private static function buildRateHistory(Investment $investment, ?Carbon $asOfDate = null): array
    {
        $resolver = app(InvestmentRateResolver::class);
        $startYear = (int) Carbon::parse($investment->start_date)->year;
        $endYear = ($asOfDate ?? now())->year;
        $history = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            try {
                $history[$year] = $resolver->resolve($investment, $year);
            } catch (\Throwable $e) {
                $history[$year] = ['rate' => null, 'source' => 'not configured'];
            }
        }

        return $history;
    }

    /**
     * Build the dynamic "Return on Investment" clause list for a combined agreement:
     * one clause per calendar year describing the rate actually applied to interest
     * that has been posted for that year (collapsed into a single clause if every
     * tranche shared the same rate that year, otherwise one clause per tranche),
     * followed by the fixed methodology clauses. Years with no posted interest are
     * omitted — this is a statement of returns actually paid, not a projection of
     * what a configured rate would yield.
     *
     * @param  Collection<int, Investment>  $investments
     * @return array<int, string>
     */
    private static function buildCombinedRateClauses(Collection $investments, Carbon $asOfDate): array
    {
        $clauses = [];

        $postedByYear = InvestmentTransaction::whereIn('investment_id', $investments->pluck('id'))
            ->where('type', InvestmentTransactionType::interest_credit->value)
            ->where('posted', true)
            ->where('date', '<=', $asOfDate)
            ->orderBy('year')
            ->get()
            ->groupBy('year');

        foreach ($postedByYear as $year => $transactions) {
            $ratesByReference = $transactions->groupBy('investment_id')->mapWithKeys(function (Collection $group) use ($investments) {
                $reference = $investments->firstWhere('id', $group->first()->investment_id)?->reference ?? $group->first()->investment_id;

                return [$reference => (float) $group->first()->rate_applied];
            });

            if ($ratesByReference->unique()->count() === 1) {
                $clauses[] = sprintf(
                    'Investments held during %d earned a return of %s%% per annum.',
                    $year,
                    number_format($ratesByReference->first(), 2)
                );
            } else {
                foreach ($ratesByReference as $reference => $rate) {
                    $clauses[] = sprintf(
                        'Investment %s held during %d earned a return of %s%% per annum.',
                        $reference,
                        $year,
                        number_format($rate, 2)
                    );
                }
            }
        }

        $clauses[] = 'Returns shall be calculated on the basis of annual compounding, with interest accruing daily using a 365-day year and prorated for partial-year periods.';
        $clauses[] = 'For each calendar year, accrued interest shall be credited and added to principal on December 31 of that year. Thereafter, interest for subsequent years shall be calculated on the increased balance consisting of original principal plus all previously credited interest.';
        $clauses[] = 'For investments funded during a calendar year, interest for the initial partial year shall be calculated from the date funds are received through December 31 of that year using the applicable annual rate, prorated based on the actual number of days invested during that year.';
        $clauses[] = 'The contractual returns described in this Section represent compensation payable by the Company on the investment loan and are not dependent upon the Company\'s profits, losses, revenues, or valuation.';

        return $clauses;
    }
}

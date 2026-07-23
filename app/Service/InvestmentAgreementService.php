<?php

namespace App\Service;

use App\Enums\InvestmentTransactionType;
use App\Exceptions\MissingInvestmentRateException;
use App\Models\Investment;
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

        return Pdf::loadView('pdf.investment-agreement', [
            'investment' => $investment,
            'investor' => $investment->investor,
            'valuation' => app(InvestmentInterestService::class)->valuationAsOf($investment, $asOfDate),
            'rateHistory' => self::buildRateHistory($investment, $asOfDate),
            'interestHistory' => $investment->transactions()
                ->where('type', InvestmentTransactionType::interest_credit->value)
                ->where('posted', true)
                ->orderBy('date')
                ->get(),
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

        $investments = $investor->investments()->orderBy('start_date')->get();

        $segmentedValuations = $investments->mapWithKeys(
            fn (Investment $investment) => [$investment->id => $interestService->segmentedValuationAsOf($investment, $asOfDate)]
        );

        return Pdf::loadView('pdf.investment-agreement-combined', [
            'investor' => $investor,
            'investments' => $investments,
            'valuations' => $segmentedValuations,
            'totalPrincipal' => $investments->sum('principal_amount'),
            'totalValue' => $segmentedValuations->sum('compounded_balance'),
            'totalInterest' => $segmentedValuations->sum('interest_earned_total'),
            'rateClauses' => self::buildCombinedRateClauses($investments, $asOfDate),
            'asOfDate' => $asOfDate,
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

        try {
            Mail::send('emails.investment-agreement', [
                'investment' => $investment,
                'investor' => $investment->investor,
            ], function ($message) use ($email, $investment, $pdf) {
                $message->to($email)
                    ->subject('Investment Agreement — '.$investment->reference)
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

        try {
            Mail::send('emails.investment-agreement-combined', [
                'investor' => $investor,
                'investments' => $investor->investments,
                'totalPrincipal' => $investor->investments->sum('principal_amount'),
            ], function ($message) use ($investor, $pdf) {
                $message->to($investor->email)
                    ->subject('Investment Agreement — '.$investor->name)
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
     * one clause per calendar year describing the rate that applied (collapsed into a
     * single clause if every tranche shared the same rate that year, otherwise one
     * clause per tranche), followed by the fixed methodology clauses.
     *
     * @param  Collection<int, Investment>  $investments
     * @return array<int, string>
     */
    private static function buildCombinedRateClauses(Collection $investments, Carbon $asOfDate): array
    {
        $resolver = app(InvestmentRateResolver::class);
        $endYear = $asOfDate->year;
        $startYear = $investments->min(fn (Investment $investment) => Carbon::parse($investment->start_date)->year) ?? $endYear;

        $clauses = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $ratesThisYear = [];

            foreach ($investments as $investment) {
                if ($year < Carbon::parse($investment->start_date)->year) {
                    continue;
                }

                try {
                    $ratesThisYear[$investment->reference] = $resolver->resolve($investment, $year)['rate'];
                } catch (MissingInvestmentRateException $e) {
                    // Not configured for this year — omit rather than guess.
                }
            }

            if (empty($ratesThisYear)) {
                continue;
            }

            if (count(array_unique($ratesThisYear)) === 1) {
                $clauses[] = sprintf(
                    'Investments held during %d earned a return of %s%% per annum.',
                    $year,
                    number_format(reset($ratesThisYear), 2)
                );
            } else {
                foreach ($ratesThisYear as $reference => $rate) {
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

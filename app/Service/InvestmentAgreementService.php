<?php

namespace App\Service;

use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\Investor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
        $investor->load('investments');
        $asOfDate ??= $investor->defaultAsOfDate();
        $interestService = app(InvestmentInterestService::class);

        $valuations = $investor->investments->mapWithKeys(
            fn (Investment $investment) => [$investment->id => $interestService->valuationAsOf($investment, $asOfDate)]
        );

        return Pdf::loadView('pdf.investment-agreement-combined', [
            'investor' => $investor,
            'investments' => $investor->investments,
            'valuations' => $valuations,
            'totalPrincipal' => $investor->investments->sum('principal_amount'),
            'totalValue' => $valuations->sum('compounded_balance'),
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
}

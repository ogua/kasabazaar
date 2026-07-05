<?php

namespace App\Service;

use App\Models\Investment;
use App\Models\Investor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class InvestmentAgreementService
{
    public static function generatePdf(Investment $investment): \Barryvdh\DomPDF\PDF
    {
        $investment->load('investor');

        return Pdf::loadView('pdf.investment-agreement', [
            'investment' => $investment,
            'investor' => $investment->investor,
            'valuation' => app(InvestmentInterestService::class)->valuationAsOf($investment, now()),
            'rateHistory' => self::buildRateHistory($investment),
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    public static function generateCombinedPdf(Investor $investor): \Barryvdh\DomPDF\PDF
    {
        $investor->load('investments');
        $interestService = app(InvestmentInterestService::class);

        $valuations = $investor->investments->mapWithKeys(
            fn (Investment $investment) => [$investment->id => $interestService->valuationAsOf($investment, now())]
        );

        return Pdf::loadView('pdf.investment-agreement-combined', [
            'investor' => $investor,
            'investments' => $investor->investments,
            'valuations' => $valuations,
            'totalPrincipal' => $investor->investments->sum('principal_amount'),
            'totalValue' => $valuations->sum('compounded_balance'),
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    public static function downloadPdf(Investment $investment)
    {
        return self::generatePdf($investment)->download("investment-agreement-{$investment->reference}.pdf");
    }

    public static function streamPdf(Investment $investment)
    {
        return self::generatePdf($investment)->stream("investment-agreement-{$investment->reference}.pdf");
    }

    public static function downloadCombinedPdf(Investor $investor)
    {
        return self::generateCombinedPdf($investor)->download("investment-agreement-{$investor->id}.pdf");
    }

    public static function streamCombinedPdf(Investor $investor)
    {
        return self::generateCombinedPdf($investor)->stream("investment-agreement-{$investor->id}.pdf");
    }

    public static function sendEmail(Investment $investment): bool
    {
        $investment->load('investor');
        $email = $investment->investor?->email;

        if (! $email) {
            return false;
        }

        $pdf = self::generatePdf($investment);

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

    /**
     * @return array<int, array{rate: ?float, source: string}>
     */
    private static function buildRateHistory(Investment $investment): array
    {
        $resolver = app(InvestmentRateResolver::class);
        $startYear = (int) Carbon::parse($investment->start_date)->year;
        $endYear = (int) now()->year;
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

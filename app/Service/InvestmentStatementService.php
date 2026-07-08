<?php

namespace App\Service;

use App\Models\Investment;
use App\Models\Investor;

class InvestmentStatementService
{
    public static function generatePdf(Investor $investor): \Barryvdh\DomPDF\PDF
    {
        $investor->load(['investments' => function ($query) {
            $query->with(['transactions' => function ($query) {
                $query->where('posted', true)->orderBy('date');
            }, 'withdrawalRequests' => function ($query) {
                $query->orderByDesc('created_at');
            }]);
        }]);

        $interestService = app(InvestmentInterestService::class);

        $valuations = $investor->investments->mapWithKeys(
            fn (Investment $investment) => [$investment->id => $interestService->valuationAsOf($investment, now())]
        );

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.investment-account-statement', [
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

    public static function downloadPdf(Investor $investor)
    {
        return self::generatePdf($investor)->download("investment-statement-{$investor->id}.pdf");
    }

    public static function streamPdf(Investor $investor)
    {
        return self::generatePdf($investor)->stream("investment-statement-{$investor->id}.pdf");
    }
}

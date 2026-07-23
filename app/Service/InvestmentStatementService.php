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

        // Anchor to the same already-closed accounting date the Investment Agreement
        // uses (Investment::defaultAsOfDate()), not "today" — otherwise this statement
        // would silently blend in unposted, in-progress accrual for the current partial
        // year and no longer tie out to the Agreement's stated balances.
        $asOfDate = $investor->defaultAsOfDate();

        $valuations = $investor->investments->mapWithKeys(
            fn (Investment $investment) => [$investment->id => $interestService->valuationAsOf($investment, $asOfDate)]
        );

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.investment-account-statement', [
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

    public static function downloadPdf(Investor $investor)
    {
        return self::generatePdf($investor)->download("investment-statement-{$investor->id}.pdf");
    }

    public static function streamPdf(Investor $investor)
    {
        return self::generatePdf($investor)->stream("investment-statement-{$investor->id}.pdf");
    }
}

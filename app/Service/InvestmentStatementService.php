<?php

namespace App\Service;

use App\Enums\InvestmentCapitalType;
use App\Models\Investment;
use App\Models\Investor;

class InvestmentStatementService
{
    public static function generatePdf(Investor $investor): \Barryvdh\DomPDF\PDF
    {
        $investor->load(['investments' => function ($query) {
            $query->with([
                'transactions' => fn ($query) => $query->where('posted', true)->orderBy('date'),
                // due/processing/paid only — skipped/reversed rows don't represent money
                // owed and would otherwise inflate "accrued" totals below.
                'interestPayouts' => fn ($query) => $query->whereIn('status', ['due', 'processing', 'paid'])->orderBy('due_date'),
                'withdrawalRequests' => fn ($query) => $query->orderByDesc('created_at'),
            ]);
        }]);

        $interestService = app(InvestmentInterestService::class);

        // Anchor to the same already-closed accounting date the Investment Agreement
        // uses (Investment::defaultAsOfDate()), not "today" — otherwise this statement
        // would silently blend in unposted, in-progress accrual for the current partial
        // year and no longer tie out to the Agreement's stated balances.
        $asOfDate = $investor->defaultAsOfDate();

        // Compounding valuation only applies to capital_type=investment — a loan's
        // balance never compounds, so calling this for a loan would misrepresent it
        // (and its "interest earned" would always read $0, since loan interest is
        // tracked separately via interestPayouts, not interest_credit transactions).
        $valuations = $investor->investments
            ->where('capital_type', InvestmentCapitalType::investment)
            ->mapWithKeys(
                fn (Investment $investment) => [$investment->id => $interestService->valuationAsOf($investment, $asOfDate)]
            );

        $loans = $investor->investments->where('capital_type', InvestmentCapitalType::loan);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.investment-account-statement', [
            'investor' => $investor,
            'investments' => $investor->investments,
            'valuations' => $valuations,
            'totalPrincipal' => $investor->investments->sum('principal_amount'),
            'totalValue' => $valuations->sum('compounded_balance') + $loans->sum('principal_amount'),
            'totalLoanInterestAccrued' => $loans->flatMap->interestPayouts->sum('amount'),
            'totalLoanInterestPaid' => $loans->flatMap->interestPayouts->sum('amount_paid'),
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

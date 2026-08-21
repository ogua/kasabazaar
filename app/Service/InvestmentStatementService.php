<?php

namespace App\Service;

use App\Enums\InvestmentCapitalType;
use App\Models\Investment;
use App\Models\Investor;

class InvestmentStatementService
{
    public static function generatePdf(Investor $investor): \Barryvdh\DomPDF\PDF
    {
        // The statement lists every tranche including converted ones — it is a
        // history document, and hiding a settled tranche would leave an unexplained
        // drop in the running balance. The *totals* below deliberately exclude them,
        // since the successor tranche already carries that capital.
        $investor->load(['investments' => function ($query) {
            $query->with([
                'transactions' => fn ($query) => $query->where('posted', true)->orderBy('date'),
                // skipped/reversed rows don't represent money earned and would inflate
                // the "accrued" totals below. 'converted' rows are kept: that interest
                // was genuinely earned, it simply became principal in a successor
                // tranche rather than being paid out in cash.
                'interestPayouts' => fn ($query) => $query->whereIn('status', ['due', 'processing', 'paid', 'converted'])->orderBy('due_date'),
                'withdrawalRequests' => fn ($query) => $query->orderByDesc('created_at'),
                // Needed by the converted-tranche history block on the statement.
                'conversionSources.conversion.targetInvestment',
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
        $liveInvestments = $investor->investments->reject(fn (Investment $investment) => $investment->isConverted());

        $valuations = $liveInvestments
            ->where('capital_type', InvestmentCapitalType::investment)
            ->mapWithKeys(
                fn (Investment $investment) => [$investment->id => $interestService->valuationAsOf($investment, $asOfDate)]
            );

        $loans = $liveInvestments->where('capital_type', InvestmentCapitalType::loan);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.investment-account-statement', [
            'investor' => $investor,
            'investments' => $investor->investments,
            'valuations' => $valuations,
            'totalPrincipal' => $liveInvestments->sum('principal_amount'),
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

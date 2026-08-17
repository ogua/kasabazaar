<?php

namespace App\Service;

use App\Enums\InvestmentCapitalType;
use App\Models\Investment;

class InvestorPortfolioSummaryService
{
    /**
     * Single source of truth for the investor's aggregate portfolio figures —
     * used by both the investor Filament panel's dashboard widget and the
     * mobile API, so the two never drift apart on money figures.
     *
     * @return array{total_principal: float, current_portfolio_value: float, total_interest_earned: float}
     */
    public function compute(string $investorId): array
    {
        $investments = Investment::where('investor_id', $investorId)
            ->with('interestPayouts')
            ->get();

        $investmentTranches = $investments->where('capital_type', InvestmentCapitalType::investment);
        $loans = $investments->where('capital_type', InvestmentCapitalType::loan);

        // A loan's current_balance never moves — it doesn't compound, by design —
        // so unlike an investment tranche, its interest earned/owed lives entirely
        // in interestPayouts, not in (current_balance - principal_amount).
        $loanPayouts = $loans->flatMap->interestPayouts;
        $loanInterestEarned = $loanPayouts->sum('amount');
        $loanInterestUnpaid = $loanPayouts
            ->filter(fn ($payout) => in_array($payout->status->value, ['due', 'processing'], true))
            ->sum(fn ($payout) => (float) $payout->amount - (float) $payout->amount_paid);

        $totalPrincipal = $investments->sum('principal_amount');

        // Current value = what the company currently holds/owes: compounding
        // investments' running balance, plus loan principal (due at maturity) and
        // any loan interest accrued but not yet paid out. Interest already paid to
        // the investor is deliberately excluded — that cash has already left the
        // company and is no longer part of what's outstanding.
        $totalCurrentValue = $investmentTranches->sum('current_balance')
            + $loans->sum('principal_amount')
            + $loanInterestUnpaid;

        $totalInterestEarned = ($investmentTranches->sum('current_balance') - $investmentTranches->sum('principal_amount'))
            + $loanInterestEarned;

        return [
            'total_principal' => round((float) $totalPrincipal, 2),
            'current_portfolio_value' => round((float) $totalCurrentValue, 2),
            'total_interest_earned' => round((float) $totalInterestEarned, 2),
        ];
    }
}

<?php

namespace App\Service;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentStatus;
use App\Enums\InvestmentWithdrawalStatus;
use App\Models\Investment;
use App\Models\InvestmentWithdrawalRequest;
use Illuminate\Support\Collection;

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
        // excludingConverted(): a tranche settled into a successor by a capital
        // conversion still exists as a row, but the successor now holds the same
        // capital. Counting both would double every figure below.
        $investments = Investment::where('investor_id', $investorId)
            ->excludingConverted()
            ->with('interestPayouts')
            ->get();

        $investmentTranches = $investments->where('capital_type', InvestmentCapitalType::investment);
        $loans = $investments->where('capital_type', InvestmentCapitalType::loan);

        // A loan's current_balance never moves — it doesn't compound, by design —
        // so unlike an investment tranche, its interest earned/owed lives entirely
        // in interestPayouts, not in (current_balance - principal_amount).
        $loanPayouts = $loans->flatMap->interestPayouts;

        // 'converted' payouts are included here on purpose: that interest was earned,
        // it merely became principal in a successor tranche instead of being paid in
        // cash. It is excluded from $loanInterestUnpaid below, since it is no longer owed.
        $loanInterestEarned = $loanPayouts
            ->reject(fn ($payout) => in_array($payout->status->value, ['skipped', 'reversed'], true))
            ->sum('amount');
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

    /**
     * The dashboard's "at a glance" facts: open holdings, the next maturity and loan
     * payout, and withdrawals still in progress. A full withdrawal has no requested
     * amount, so it counts at the tranche's current balance.
     *
     * @return array{holdings: Collection<int, Investment>, active_count: int, next_maturity_date: ?string, next_payout_date: ?string, pending_withdrawal_count: int, pending_withdrawal_amount: float}
     */
    public function facts(string $investorId): array
    {
        $holdings = Investment::where('investor_id', $investorId)
            ->excludingConverted()
            ->whereIn('status', [InvestmentStatus::active->value, InvestmentStatus::pending_payment->value])
            ->orderBy('maturity_date')
            ->get();

        $active = $holdings->where('status', InvestmentStatus::active);

        $pendingWithdrawals = InvestmentWithdrawalRequest::where('investor_id', $investorId)
            ->whereIn('status', [
                InvestmentWithdrawalStatus::submitted->value,
                InvestmentWithdrawalStatus::under_review->value,
                InvestmentWithdrawalStatus::approved->value,
                InvestmentWithdrawalStatus::processing->value,
            ])
            ->with('investment:id,current_balance')
            ->get();

        return [
            'holdings' => $holdings,
            'active_count' => $active->count(),
            'next_maturity_date' => $active->whereNotNull('maturity_date')
                ->filter(fn (Investment $investment) => $investment->maturity_date->isFuture())
                ->min('maturity_date')?->toDateString(),
            'next_payout_date' => $active->where('capital_type', InvestmentCapitalType::loan)
                ->whereNotNull('next_payout_due_date')
                ->min('next_payout_due_date')?->toDateString(),
            'pending_withdrawal_count' => $pendingWithdrawals->count(),
            'pending_withdrawal_amount' => round((float) $pendingWithdrawals->sum(
                fn (InvestmentWithdrawalRequest $request) => $request->is_full_withdrawal
                    ? (float) $request->investment?->current_balance
                    : (float) $request->requested_amount
            ), 2),
        ];
    }
}

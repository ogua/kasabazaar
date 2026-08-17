<?php

namespace App\Filament\Investor\Widgets;

use App\Enums\InvestmentCapitalType;
use App\Models\Investment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvestorPortfolioSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $investments = Investment::where('investor_id', auth()->user()->investor_id)
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
            Stat::make('Total Principal Invested', '$'.number_format($totalPrincipal, 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Current Portfolio Value', '$'.number_format($totalCurrentValue, 2))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),

            Stat::make('Total Interest Earned', '$'.number_format($totalInterestEarned, 2))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($totalInterestEarned >= 0 ? 'success' : 'danger'),
        ];
    }
}

<?php

namespace App\Filament\Investor\Widgets;

use App\Models\Investment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvestorPortfolioSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $investments = Investment::where('investor_id', auth()->user()->investor_id)->get();

        $totalPrincipal = $investments->sum('principal_amount');
        $totalCurrentValue = $investments->sum('current_balance');
        $totalInterestEarned = $totalCurrentValue - $totalPrincipal;

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

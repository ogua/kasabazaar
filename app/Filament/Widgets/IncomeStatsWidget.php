<?php

namespace App\Filament\Widgets;

use App\Models\Income;
use App\Enums\IncomeStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IncomeStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $thisMonthIncome = Income::where('income_date', '>=', $thisMonth)
            ->where('status', IncomeStatus::Received)
            ->sum('amount_usd');

        $lastMonthIncome = Income::where('income_date', '>=', $lastMonth)
            ->where('income_date', '<', $thisMonth)
            ->where('status', IncomeStatus::Received)
            ->sum('amount_usd');

        $pendingIncome = Income::where('status', IncomeStatus::Pending)->sum('amount_usd');

        $change = $lastMonthIncome > 0
            ? (($thisMonthIncome - $lastMonthIncome) / $lastMonthIncome) * 100
            : 0;

        return [
            Stat::make('Monthly External Income', '$' . number_format($thisMonthIncome, 2))
                ->description($change >= 0 ? abs(round($change, 1)) . '% increase' : abs(round($change, 1)) . '% decrease')
                ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($change >= 0 ? 'success' : 'danger'),

            Stat::make('Pending Income', '$' . number_format($pendingIncome, 2))
                ->description('Awaiting receipt')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total External Income (YTD)', '$' . number_format(
                Income::whereYear('income_date', now()->year)
                    ->where('status', IncomeStatus::Received)
                    ->sum('amount_usd'),
                2
            ))
                ->description('Year to date')
                ->descriptionIcon('heroicon-m-calendar'),
        ];
    }
}

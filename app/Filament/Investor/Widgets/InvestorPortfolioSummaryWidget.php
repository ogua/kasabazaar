<?php

namespace App\Filament\Investor\Widgets;

use App\Service\InvestorPortfolioSummaryService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvestorPortfolioSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $summary = app(InvestorPortfolioSummaryService::class)->compute(auth()->user()->investor_id);

        return [
            Stat::make('Total Principal Invested', '$'.number_format($summary['total_principal'], 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Current Portfolio Value', '$'.number_format($summary['current_portfolio_value'], 2))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),

            Stat::make('Total Interest Earned', '$'.number_format($summary['total_interest_earned'], 2))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($summary['total_interest_earned'] >= 0 ? 'success' : 'danger'),
        ];
    }
}

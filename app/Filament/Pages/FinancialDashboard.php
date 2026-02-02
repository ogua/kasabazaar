<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\ExpenseStatsWidget;
use App\Filament\Widgets\IncomeStatsWidget;
use App\Filament\Widgets\ExpensesByCategoryChart;
use App\Filament\Widgets\MonthlyExpenseIncomeChart;
use App\Filament\Widgets\PayrollStatsWidget;

class FinancialDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.pages.financial-dashboard';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Financial Dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            FinancialOverviewWidget::class,
            ExpenseStatsWidget::class,
            IncomeStatsWidget::class,
            PayrollStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ExpensesByCategoryChart::class,
            MonthlyExpenseIncomeChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}

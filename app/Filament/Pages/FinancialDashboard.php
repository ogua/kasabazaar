<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\TopStatesWidget;
use App\Filament\Widgets\IncomeStatsWidget;
use App\Filament\Widgets\ExpenseStatsWidget;
use App\Filament\Widgets\PayrollStatsWidget;
use App\Filament\Widgets\ManagementKPIWidget;
use App\Filament\Widgets\ContainerProfitWidget;
use App\Filament\Widgets\ExpensesByCategoryChart;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\MonthlyExpenseIncomeChart;

class FinancialDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.pages.financial-dashboard';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Financial Dashboard';

    protected static ?string $navigationLabel = 'Financial Dashboard';

    public static function getNavigationBadge(): ?string
    {
        return 'Management';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinancialOverviewWidget::class,
            ManagementKPIWidget::class,
            ContainerProfitWidget::class,
            TopStatesWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ExpenseStatsWidget::class,
            IncomeStatsWidget::class,
            PayrollStatsWidget::class,
            ExpensesByCategoryChart::class,
            MonthlyExpenseIncomeChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1; // Full width for comprehensive widgets
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 3; // 3 columns for footer stats
    }
}

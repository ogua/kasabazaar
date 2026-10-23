<?php

namespace App\Filament\Investor\Pages;

use App\Filament\Investor\Widgets\CompanyPerformanceWidget;
use App\Filament\Investor\Widgets\ContainerProfiInvestortWidget;
use App\Filament\Investor\Widgets\InvestorPortfolioSummaryWidget;
use App\Filament\Widgets\ClientGrowthChart;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\ShipmentsByContainerChart;
use App\Filament\Widgets\TopStatesWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;

/**
 * The investor dashboard is split into two colour-coded zones so an investor
 * never mistakes company-wide figures (income, costs, container profit) for
 * their own money: an emerald "My Investment" zone on top, and a blue
 * "Company Performance" zone below it.
 */
class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.investor.pages.dashboard';

    /**
     * @return array<class-string<Widget>>
     */
    public function getInvestorWidgets(): array
    {
        return $this->filterVisibleWidgets([
            InvestorPortfolioSummaryWidget::class,
        ]);
    }

    /**
     * @return array<class-string<Widget>>
     */
    public function getCompanyWidgets(): array
    {
        return $this->filterVisibleWidgets([
            FinancialOverviewWidget::class,
            CompanyPerformanceWidget::class,
            ContainerProfiInvestortWidget::class,
            TopStatesWidget::class,
            ShipmentsByContainerChart::class,
            ClientGrowthChart::class,
        ]);
    }

    /**
     * @return array<class-string<Widget>>
     */
    public function getWidgets(): array
    {
        return [...$this->getInvestorWidgets(), ...$this->getCompanyWidgets()];
    }
}

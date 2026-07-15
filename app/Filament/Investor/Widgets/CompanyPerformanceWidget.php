<?php

namespace App\Filament\Investor\Widgets;

use App\Service\InvestorCompanyPerformanceService;
use Filament\Widgets\ChartWidget;

class CompanyPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = 'Company Performance (Monthly Revenue, USD)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $trend = app(InvestorCompanyPerformanceService::class)->monthlyRevenueTrend();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (USD)',
                    'data' => $trend->pluck('revenue_usd'),
                    'backgroundColor' => 'rgba(160, 4, 60, 0.5)',
                    'borderColor' => 'rgb(160, 4, 60)',
                ],
            ],
            'labels' => $trend->pluck('month'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

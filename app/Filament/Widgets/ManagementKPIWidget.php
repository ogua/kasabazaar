<?php

namespace App\Filament\Widgets;

use App\Models\Shipment;
use App\Services\ManagementKpiService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class ManagementKPIWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?string $containerNumber = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->containerNumber = null;
    }

    #[On('dashboardFiltersUpdated')]
    #[On('filtersUpdated')]
    public function updateFilters($start_date, $end_date, $container_number = null): void
    {
        $this->startDate = $start_date;
        $this->endDate = $end_date;
        $this->containerNumber = $container_number;
    }

    protected function getStats(): array
    {
        $startDate = $this->startDate ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->endDate ?? now()->format('Y-m-d');
        $containerNumber = $this->containerNumber;

        [
            'period_days' => $days,
            'current_rate' => $currentRate,
            'avg_shipment_value' => $avgShipmentValue,
            'collection_rate' => $collectionRate,
            'avg_payment_days' => $avgPaymentDays,
            'active_clients' => $activeClients,
            'new_clients' => $newClients,
            'total_clients' => $totalClients,
            'revenue_ghs' => $monthRevenue,
            'previous_revenue_ghs' => $lastMonthRevenue,
            'revenue_growth' => $revenueGrowth,
            'shipments' => $thisMonthShipments,
            'previous_shipments' => $lastMonthShipments,
            'shipment_growth' => $shipmentGrowth,
            'outstanding_receivables' => $outstandingReceivables,
            'dso' => $dso,
            'expense_ratio' => $expenseRatio,
        ] = app(ManagementKpiService::class)->compute($startDate, $endDate, $containerNumber);

        // Period label
        $periodLabel = $days <= 31 ? "({$days} days)" : '('.round($days / 30, 1).' months)';
        $filterLabel = $containerNumber ? ' [CON'.$containerNumber.']' : '';

        return [
            Stat::make('Avg. Shipment Value', '$'.number_format($avgShipmentValue, 2))
                ->description('Average value per shipment')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info')
                ->chart($this->getShipmentValueTrend()),

            Stat::make('Collection Efficiency', number_format($collectionRate, 1).'%')
                ->description(
                    $collectionRate >= 80
                        ? 'Excellent collection rate'
                        : ($collectionRate >= 60 ? 'Good collection rate' : 'Needs improvement')
                )
                ->descriptionIcon($collectionRate >= 80 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($collectionRate >= 80 ? 'success' : ($collectionRate >= 60 ? 'warning' : 'danger')),

            Stat::make('Avg. Payment Time', number_format($avgPaymentDays, 1).' days')
                ->description(
                    $avgPaymentDays <= 7
                        ? 'Fast payment processing'
                        : ($avgPaymentDays <= 14 ? 'Moderate processing' : 'Slow payment processing')
                )
                ->descriptionIcon('heroicon-m-clock')
                ->color($avgPaymentDays <= 7 ? 'success' : ($avgPaymentDays <= 14 ? 'warning' : 'danger')),

            Stat::make('Active Clients', $activeClients)
                ->description('New: '.$newClients.' | Total: '.$totalClients)
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Revenue Growth', number_format(abs($revenueGrowth), 1).'%')
                ->description(
                    $revenueGrowth >= 0
                        ? 'vs last month (+$'.number_format($currentRate > 0 ? ($monthRevenue - $lastMonthRevenue) / $currentRate : 0, 2).')'
                        : 'vs last month (-$'.number_format($currentRate > 0 ? ($lastMonthRevenue - $monthRevenue) / $currentRate : 0, 2).')'
                )
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getRevenueGrowthTrend()),

            Stat::make('Shipment Growth', number_format(abs($shipmentGrowth), 1).'%')
                ->description(
                    $shipmentGrowth >= 0
                        ? 'vs last month (+'.($thisMonthShipments - $lastMonthShipments).' shipments)'
                        : 'vs last month (-'.($lastMonthShipments - $thisMonthShipments).' shipments)'
                )
                ->descriptionIcon($shipmentGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($shipmentGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Outstanding Receivables', '$'.number_format($outstandingReceivables, 2))
                ->description('Days Sales Outstanding: '.number_format($dso, 1).' days')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($dso <= 30 ? 'success' : ($dso <= 60 ? 'warning' : 'danger')),

            Stat::make('Operating Efficiency', number_format(100 - $expenseRatio, 1).'%')
                ->description('Expense Ratio: '.number_format($expenseRatio, 1).'% of revenue')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($expenseRatio <= 30 ? 'success' : ($expenseRatio <= 50 ? 'warning' : 'danger')),
        ];
    }

    protected function getShipmentValueTrend(): array
    {
        $startDate = $this->startDate ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->endDate ?? now()->format('Y-m-d');
        $containerNumber = $this->containerNumber;

        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        $interval = max(1, floor($days / 7));

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::parse($endDate)->subDays($i * $interval)->startOfDay();
            if ($date->lt(\Carbon\Carbon::parse($startDate))) {
                continue;
            }

            $query = Shipment::whereDate('created_at', $date);
            if ($containerNumber) {
                $query->where('container_number', $containerNumber);
            }
            $avg = $query->avg('total') ?: 0;
            $data[] = $avg;
        }

        return $data;
    }

    protected function getRevenueGrowthTrend(): array
    {
        $startDate = $this->startDate ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->endDate ?? now()->format('Y-m-d');
        $containerNumber = $this->containerNumber;

        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        $interval = max(1, floor($days / 7));

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::parse($endDate)->subDays($i * $interval)->startOfDay();
            if ($date->lt(\Carbon\Carbon::parse($startDate))) {
                continue;
            }

            $query = Shipment::whereDate('created_at', $date);
            if ($containerNumber) {
                $query->where('container_number', $containerNumber);
            }
            $revenue = $query->sum('total_ghs') ?: 0;
            $data[] = $revenue;
        }

        return $data;
    }
}

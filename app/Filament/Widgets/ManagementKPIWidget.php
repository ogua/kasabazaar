<?php

namespace App\Filament\Widgets;

use App\Models\Shipment;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Client;
use App\Service\ExchangeRateService;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class ManagementKPIWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

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

        // Calculate previous period
        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        $prevEndDate = \Carbon\Carbon::parse($startDate)->subDay()->format('Y-m-d');
        $prevStartDate = \Carbon\Carbon::parse($prevEndDate)->subDays($days - 1)->format('Y-m-d');

        $exchangeService = app(ExchangeRateService::class);
        $currentRate = $exchangeService->getCurrentRate('USD', 'GHS');

        // === CURRENT PERIOD METRICS ===

        // Average Shipment Value
        $avgShipmentQuery = Shipment::whereBetween('created_at', [$startDate, $endDate]);
        if ($containerNumber) {
            $avgShipmentQuery->where('container_number', $containerNumber);
        }
        $avgShipmentValue = $avgShipmentQuery->avg('total_ghs');

        // Collection Efficiency (Payments received vs Revenue generated)
        $monthRevenueQuery = Shipment::whereBetween('created_at', [$startDate, $endDate]);
        if ($containerNumber) {
            $monthRevenueQuery->where('container_number', $containerNumber);
        }
        $monthRevenue = $monthRevenueQuery->sum('total_ghs');

        $monthPaymentsQuery = Payment::where('payment_type', 'credit')
            ->whereBetween('paid_on', [$startDate, $endDate]);
        if ($containerNumber) {
            $monthPaymentsQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }
        $monthPayments = $monthPaymentsQuery->sum('amount_ghs');

        $collectionRate = $monthRevenue > 0 ? ($monthPayments / $monthRevenue) * 100 : 0;

        // Average Payment Processing Time (days from shipment to first payment)
        $avgPaymentQuery = DB::table('shipments')
            ->join('payments', 'payments.shipment_id', '=', 'shipments.id')
            ->whereBetween('shipments.created_at', [$startDate, $endDate])
            ->where('payments.payment_type', 'credit');
        if ($containerNumber) {
            $avgPaymentQuery->where('shipments.container_number', $containerNumber);
        }
        $avgPaymentDays = $avgPaymentQuery
            ->selectRaw('AVG(DATEDIFF(payments.paid_on, shipments.created_at)) as avg_days')
            ->value('avg_days') ?: 0;

        // Active Clients This Period
        $activeClientsQuery = Shipment::whereBetween('created_at', [$startDate, $endDate]);
        if ($containerNumber) {
            $activeClientsQuery->where('container_number', $containerNumber);
        }
        $activeClients = $activeClientsQuery->distinct('client_id')->count('client_id');

        // === PREVIOUS PERIOD COMPARISON ===

        $lastMonthRevenueQuery = Shipment::whereBetween('created_at', [$prevStartDate, $prevEndDate]);
        if ($containerNumber) {
            $lastMonthRevenueQuery->where('container_number', $containerNumber);
        }
        $lastMonthRevenue = $lastMonthRevenueQuery->sum('total_ghs');

        $revenueGrowth = $lastMonthRevenue > 0
            ? (($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        $lastMonthShipmentsQuery = Shipment::whereBetween('created_at', [$prevStartDate, $prevEndDate]);
        if ($containerNumber) {
            $lastMonthShipmentsQuery->where('container_number', $containerNumber);
        }
        $lastMonthShipments = $lastMonthShipmentsQuery->count();

        $thisMonthShipmentsQuery = Shipment::whereBetween('created_at', [$startDate, $endDate]);
        if ($containerNumber) {
            $thisMonthShipmentsQuery->where('container_number', $containerNumber);
        }
        $thisMonthShipments = $thisMonthShipmentsQuery->count();

        $shipmentGrowth = $lastMonthShipments > 0
            ? (($thisMonthShipments - $lastMonthShipments) / $lastMonthShipments) * 100
            : 0;

        // === OPERATIONAL METRICS ===

        // Outstanding Receivables
        $outstandingQuery = DB::table('shipments')
            ->leftJoin('payments', function($join) {
                $join->on('payments.shipment_id', '=', 'shipments.id')
                    ->where('payments.payment_type', '=', 'credit');
            });
        if ($containerNumber) {
            $outstandingQuery->where('shipments.container_number', $containerNumber);
        }
        $outstandingReceivables = $outstandingQuery
            ->selectRaw('
                SUM(shipments.total_ghs) - COALESCE(SUM(payments.amount_ghs), 0) as outstanding
            ')
            ->value('outstanding') ?: 0;

        // Total Debt/Receivables Ratio (Days Sales Outstanding equivalent)
        $dailyRevenue = $monthRevenue / max($days, 1);
        $dso = $dailyRevenue > 0 ? $outstandingReceivables / $dailyRevenue : 0;

        // Expense to Revenue Ratio
        $monthExpensesQuery = Expense::whereBetween('expense_date', [$startDate, $endDate]);
        if ($containerNumber) {
            $monthExpensesQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }
        $monthExpenses = $monthExpensesQuery->sum('amount_ghs');

        $expenseRatio = $monthRevenue > 0 ? ($monthExpenses / $monthRevenue) * 100 : 0;

        // New Clients This Period
        $newClients = Client::whereBetween('created_at', [$startDate, $endDate])->count();

        // Period label
        $periodLabel = $days <= 31 ? "({$days} days)" : "(" . round($days / 30, 1) . " months)";
        $filterLabel = $containerNumber ? ' [CON' . $containerNumber . ']' : '';

        return [
            Stat::make('Avg. Shipment Value', '₵' . number_format($avgShipmentValue, 2))
                ->description('$' . number_format($currentRate > 0 ? $avgShipmentValue / $currentRate : 0, 2) . ' USD equivalent')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info')
                ->chart($this->getShipmentValueTrend()),

            Stat::make('Collection Efficiency', number_format($collectionRate, 1) . '%')
                ->description(
                    $collectionRate >= 80
                        ? 'Excellent collection rate'
                        : ($collectionRate >= 60 ? 'Good collection rate' : 'Needs improvement')
                )
                ->descriptionIcon($collectionRate >= 80 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($collectionRate >= 80 ? 'success' : ($collectionRate >= 60 ? 'warning' : 'danger')),

            Stat::make('Avg. Payment Time', number_format($avgPaymentDays, 1) . ' days')
                ->description(
                    $avgPaymentDays <= 7
                        ? 'Fast payment processing'
                        : ($avgPaymentDays <= 14 ? 'Moderate processing' : 'Slow payment processing')
                )
                ->descriptionIcon('heroicon-m-clock')
                ->color($avgPaymentDays <= 7 ? 'success' : ($avgPaymentDays <= 14 ? 'warning' : 'danger')),

            Stat::make('Active Clients', $activeClients)
                ->description('New: ' . $newClients . ' | Total: ' . Client::count())
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Revenue Growth', number_format(abs($revenueGrowth), 1) . '%')
                ->description(
                    $revenueGrowth >= 0
                        ? 'vs last month (+₵' . number_format($monthRevenue - $lastMonthRevenue, 2) . ')'
                        : 'vs last month (-₵' . number_format($lastMonthRevenue - $monthRevenue, 2) . ')'
                )
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getRevenueGrowthTrend()),

            Stat::make('Shipment Growth', number_format(abs($shipmentGrowth), 1) . '%')
                ->description(
                    $shipmentGrowth >= 0
                        ? 'vs last month (+' . ($thisMonthShipments - $lastMonthShipments) . ' shipments)'
                        : 'vs last month (-' . ($lastMonthShipments - $thisMonthShipments) . ' shipments)'
                )
                ->descriptionIcon($shipmentGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($shipmentGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Outstanding Receivables', '₵' . number_format($outstandingReceivables, 2))
                ->description('Days Sales Outstanding: ' . number_format($dso, 1) . ' days')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($dso <= 30 ? 'success' : ($dso <= 60 ? 'warning' : 'danger')),

            Stat::make('Operating Efficiency', number_format(100 - $expenseRatio, 1) . '%')
                ->description('Expense Ratio: ' . number_format($expenseRatio, 1) . '% of revenue')
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
            $avg = $query->avg('total_ghs') ?: 0;
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

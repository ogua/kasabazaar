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

class ManagementKPIWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $exchangeService = app(ExchangeRateService::class);
        $currentRate = $exchangeService->getCurrentRate('USD', 'GHS');

        // === THIS MONTH METRICS ===

        // Average Shipment Value
        $avgShipmentValue = Shipment::where('created_at', '>=', $thisMonth)
            ->avg('total');

        // Collection Efficiency (Payments received vs Revenue generated)
        $monthRevenue = Shipment::where('created_at', '>=', $thisMonth)
            ->sum('total_ghs');

        $monthPayments = Payment::where('payment_type', 'credit')
            ->where('paid_on', '>=', $thisMonth)
            ->sum('amount_ghs');

        $collectionRate = $monthRevenue > 0 ? ($monthPayments / $monthRevenue) * 100 : 0;

        // Average Payment Processing Time (days from shipment to first payment)
        $avgPaymentDays = DB::table('shipments')
            ->join('payments', 'payments.shipment_id', '=', 'shipments.id')
            ->where('shipments.created_at', '>=', $thisMonth)
            ->where('payments.payment_type', 'credit')
            ->selectRaw('AVG(DATEDIFF(payments.paid_on, shipments.created_at)) as avg_days')
            ->value('avg_days') ?: 0;

        // Active Clients This Month
        $activeClients = Shipment::where('created_at', '>=', $thisMonth)
            ->distinct('client_id')
            ->count('client_id');

        // === LAST MONTH COMPARISON ===

        $lastMonthRevenue = Shipment::whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->sum('total_ghs');

        $revenueGrowth = $lastMonthRevenue > 0
            ? (($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        $lastMonthShipments = Shipment::whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->count();

        $thisMonthShipments = Shipment::where('created_at', '>=', $thisMonth)
            ->count();

        $shipmentGrowth = $lastMonthShipments > 0
            ? (($thisMonthShipments - $lastMonthShipments) / $lastMonthShipments) * 100
            : 0;

        // === OPERATIONAL METRICS ===

        // Outstanding Receivables
        $outstandingReceivables = DB::table('shipments')
            ->leftJoin('payments', function($join) {
                $join->on('payments.shipment_id', '=', 'shipments.id')
                    ->where('payments.payment_type', '=', 'credit');
            })
            ->selectRaw('
                SUM(shipments.total_ghs) - COALESCE(SUM(payments.amount_ghs), 0) as outstanding
            ')
            ->value('outstanding') ?: 0;

        // Total Debt/Receivables Ratio (Days Sales Outstanding equivalent)
        $dailyRevenue = $monthRevenue / max(now()->day, 1);
        $dso = $dailyRevenue > 0 ? $outstandingReceivables / $dailyRevenue : 0;

        // Expense to Revenue Ratio
        $monthExpenses = Expense::where('expense_date', '>=', $thisMonth)
            ->sum('amount_ghs');

        $expenseRatio = $monthRevenue > 0 ? ($monthExpenses / $monthRevenue) * 100 : 0;

        // New Clients This Month
        $newClients = Client::where('created_at', '>=', $thisMonth)->count();

        return [
            Stat::make('Avg. Shipment Value', '$' . number_format($avgShipmentValue, 2))
                ->description('₵' . number_format($avgShipmentValue * $currentRate, 2) . ' at current rate')
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
        // Last 7 days average shipment value
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $avg = Shipment::whereDate('created_at', $date)->avg('total') ?: 0;
            $data[] = $avg;
        }
        return $data;
    }

    protected function getRevenueGrowthTrend(): array
    {
        // Last 7 days revenue
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $revenue = Shipment::whereDate('created_at', $date)->sum('total_ghs') ?: 0;
            $data[] = $revenue;
        }
        return $data;
    }
}

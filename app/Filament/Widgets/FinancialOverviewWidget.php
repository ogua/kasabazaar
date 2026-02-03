<?php

namespace App\Filament\Widgets;

use App\Models\Income;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Shipment;
use App\Enums\IncomeStatus;
use App\Models\PayrollEntry;
use App\Enums\PayrollEntryStatus;
use App\Service\ExchangeRateService;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $thisMonth = now()->startOfMonth();
        $exchangeService = app(ExchangeRateService::class);
        $currentRate = $exchangeService->getCurrentRate('USD', 'GHS');

        // 1. Shipment Income (Payments received for shipments) - PROPERLY CALCULATED IN GHS
        $shipmentPayments = Payment::where('payment_type', 'credit')
            ->where('paid_on', '>=', $thisMonth)
            ->get();

        $shipmentIncomeUsd = $shipmentPayments->sum('amount_usd') ?: $shipmentPayments->sum('amount');
        $shipmentIncomeGhs = $shipmentPayments->sum('amount_ghs') ?: ($shipmentIncomeUsd * $currentRate);

        // 2. External Income (Other income sources)
        $externalIncomeGhs = Income::where('status', IncomeStatus::Received)
            ->where('income_date', '>=', $thisMonth)
            ->sum('amount_ghs');

        // Total Income in GHS
        $totalIncomeGhs = $shipmentIncomeGhs + $externalIncomeGhs;

        // 3. Expenses (all expenses including shipment-related)
        $totalExpenses = Expense::where('expense_date', '>=', $thisMonth)
            ->sum('amount_ghs');

        // 4. Payroll
        $totalPayroll = PayrollEntry::whereHas('payrollPeriod', function ($query) use ($thisMonth) {
            $query->where('pay_date', '>=', $thisMonth);
        })->sum('net_salary');

        // Total Costs
        $totalCosts = $totalExpenses + $totalPayroll;

        // Net Profit/Loss
        $netProfit = $totalIncomeGhs - $totalCosts;

        // 5. Unpaid Shipments
        $unpaidShipmentsData = DB::table('shipments')
            ->leftJoin('payments', function($join) {
                $join->on('payments.shipment_id', '=', 'shipments.id')
                    ->where('payments.payment_type', '=', 'credit');
            })
            ->selectRaw('
                shipments.id,
                shipments.total,
                shipments.total_ghs,
                COALESCE(SUM(payments.amount_usd), SUM(payments.amount), 0) as paid_amount_usd,
                COALESCE(SUM(payments.amount_ghs), 0) as paid_amount_ghs
            ')
            ->groupBy('shipments.id', 'shipments.total', 'shipments.total_ghs')
            ->havingRaw('COALESCE(paid_amount_usd, 0) < shipments.total')
            ->get();

        $unpaidShipments = $unpaidShipmentsData->count();
        $unpaidAmountUsd = $unpaidShipmentsData->sum(function ($item) {
            return $item->total - ($item->paid_amount_usd ?: 0);
        });
        $unpaidAmountGhs = $unpaidShipmentsData->sum(function ($item) {
            return ($item->total_ghs ?: 0) - ($item->paid_amount_ghs ?: 0);
        });

        return [
            Stat::make('Total Income (Monthly)', '₵' . number_format($totalIncomeGhs, 2))
                ->description("Shipments: ₵" . number_format($shipmentIncomeGhs, 2) . " ($" . number_format($shipmentIncomeUsd, 2) . ") | External: ₵" . number_format($externalIncomeGhs, 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getIncomeChart()),

            Stat::make('Total Costs (Monthly)', '₵' . number_format($totalCosts, 2))
                ->description("Expenses: ₵" . number_format($totalExpenses, 2) . " | Payroll: ₵" . number_format($totalPayroll, 2))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger')
                ->chart($this->getCostChart()),

            Stat::make('Net Profit/Loss (Monthly)', '₵' . number_format($netProfit, 2))
                ->description(
                    $netProfit >= 0
                        ? 'Profitable: ' . number_format(($netProfit / ($totalIncomeGhs ?: 1)) * 100, 1) . '% margin'
                        : 'Loss: ₵' . number_format(abs($netProfit), 2)
                )
                ->descriptionIcon($netProfit >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Unpaid Shipments', $unpaidShipments)
                ->description('Outstanding: $' . number_format($unpaidAmountUsd, 2) . ' (₵' . number_format($unpaidAmountGhs, 2) . ')')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($unpaidShipments > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.shipments.index', [
                    'tenant' => \Filament\Facades\Filament::getTenant(),
                    'tableFilters' => ['payment_status' => ['value' => 'unpaid']]
                ])),

            Stat::make('Current Exchange Rate', '$1 = ₵' . number_format($currentRate, 2))
                ->description('USD to GHS conversion rate')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
        ];
    }

    protected function getIncomeChart(): array
    {
        // Last 7 days income trend in GHS
        $data = [];
        $exchangeService = app(ExchangeRateService::class);

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();

            // Shipment payments for this day
            $shipmentPayments = Payment::where('payment_type', 'credit')
                ->whereDate('paid_on', $date)
                ->get();

            $shipmentIncomeGhs = $shipmentPayments->sum('amount_ghs') ?:
                ($shipmentPayments->sum('amount_usd') ?: $shipmentPayments->sum('amount')) * $exchangeService->getCurrentRate('USD', 'GHS');

            // External income
            $externalIncome = Income::where('status', IncomeStatus::Received)
                ->whereDate('income_date', $date)
                ->sum('amount_ghs');

            $data[] = $shipmentIncomeGhs + $externalIncome;
        }
        return $data;
    }

    protected function getCostChart(): array
    {
        // Last 7 days cost trend
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $expenses = Expense::whereDate('expense_date', $date)->sum('amount_ghs');
            $payroll = PayrollEntry::whereHas('payrollPeriod', function ($query) use ($date) {
                $query->whereDate('pay_date', $date);
            })->sum('net_salary');
            $data[] = $expenses + $payroll;
        }
        return $data;
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Income;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Shipment;
use App\Enums\IncomeStatus;
use App\Models\PayrollEntry;
use App\Enums\PayrollEntryStatus;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $thisMonth = now()->startOfMonth();

        // 1. Shipment Income (Payments received for shipments)
        $shipmentIncome = Payment::where('payment_type', 'credit')
            ->where('paid_on', '>=', $thisMonth)
            ->sum('amount');

        // 2. External Income (Other income sources)
        $externalIncome = Income::where('status', IncomeStatus::Received)
            ->where('income_date', '>=', $thisMonth)
            ->sum('amount_ghs');

        // Total Income
        $totalIncome = $shipmentIncome + $externalIncome;

        // 3. Expenses
        $totalExpenses = Expense::where('expense_date', '>=', $thisMonth)
            ->sum('amount_ghs');

        // 4. Payroll
        $totalPayroll = PayrollEntry::whereHas('payrollPeriod', function ($query) use ($thisMonth) {
            $query->where('pay_date', '>=', $thisMonth);
        })->sum('net_salary');

        // Total Costs
        $totalCosts = $totalExpenses + $totalPayroll;

        // Net Profit
        $netProfit = $totalIncome - $totalCosts;

        // 5. Unpaid Shipments (Shipments without full payment)
        // Use subquery to avoid GROUP BY issues
        // 5. Unpaid Shipments - Using LEFT JOIN (more efficient)
        $unpaidShipmentsData = DB::table('shipments')
            ->leftJoin('payments', function($join) {
                $join->on('payments.shipment_id', '=', 'shipments.id')
                    ->where('payments.payment_type', '=', 'credit');
            })
            ->selectRaw('
                shipments.id,
                shipments.total,
                COALESCE(SUM(payments.amount), 0) as paid_amount
            ')
            ->groupBy('shipments.id', 'shipments.total')
            ->havingRaw('paid_amount < shipments.total')
            ->get();

        $unpaidShipments = $unpaidShipmentsData->count();
        $unpaidAmount = $unpaidShipmentsData->sum(function ($item) {
            return $item->total - $item->paid_amount;
        });

        return [
            Stat::make('Total Income (Monthly)', '₵' . number_format($totalIncome, 2))
                ->description("Shipments: ₵" . number_format($shipmentIncome, 2) . " | External: ₵" . number_format($externalIncome, 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getIncomeChart()),

            Stat::make('Total Costs (Monthly)', '₵' . number_format($totalCosts, 2))
                ->description("Expenses: ₵" . number_format($totalExpenses, 2) . " | Payroll: ₵" . number_format($totalPayroll, 2))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger')
                ->chart($this->getCostChart()),

            Stat::make('Net Profit (Monthly)', '₵' . number_format($netProfit, 2))
                ->description($netProfit >= 0 ? 'Profitable' : 'Loss')
                ->descriptionIcon($netProfit >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Unpaid Shipments', $unpaidShipments)
                ->description('Outstanding: ₵' . number_format($unpaidAmount, 2))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($unpaidShipments > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.shipments.index', [
                    'tenant' => \Filament\Facades\Filament::getTenant(),
                    'tableFilters' => ['payment_status' => ['value' => 'unpaid']]
                ])),
        ];
    }

    protected function getIncomeChart(): array
    {
        // Last 7 days income trend
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $shipmentIncome = Payment::where('payment_type', 'credit')
                ->whereDate('paid_on', $date)
                ->sum('amount');
            $externalIncome = Income::where('status', IncomeStatus::Received)
                ->whereDate('income_date', $date)
                ->sum('amount_ghs');
            $data[] = $shipmentIncome + $externalIncome;
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
            $data[] = $expenses;
        }
        return $data;
    }
}

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
use Livewire\Attributes\On;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
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

    #[On('dateRangeUpdated')]
    public function updateDateRange($start_date, $end_date): void
    {
        $this->startDate = $start_date;
        $this->endDate = $end_date;
    }

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

        $exchangeService = app(ExchangeRateService::class);
        $currentRate = $exchangeService->getCurrentRate('USD', 'GHS');

        // 1. Shipment Income (Payments received for shipments) - PROPERLY CALCULATED IN GHS
        $shipmentPaymentsQuery = Payment::where('payment_type', 'credit')
            ->whereBetween('paid_on', [$startDate, $endDate]);

        if ($containerNumber) {
            // Filter by container - join with shipments table
            $shipmentPaymentsQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }

        $shipmentPayments = $shipmentPaymentsQuery->get();

        // Calculate USD: use amount_usd if available, otherwise use amount field
        $shipmentIncomeUsd = $shipmentPayments->sum(function ($payment) {
            return $payment->amount_usd ?? $payment->amount ?? 0;
        });

        // Calculate GHS: use amount_ghs if available, otherwise convert USD to GHS
        $shipmentIncomeGhs = $shipmentPayments->sum(function ($payment) use ($currentRate) {
            if ($payment->amount_ghs) {
                return $payment->amount_ghs;
            }
            $usd = $payment->amount_usd ?? $payment->amount ?? 0;
            return $usd * $currentRate;
        });

        // 2. External Income (Other income sources)
        $externalIncomeQuery = Income::where('status', IncomeStatus::Received)
            ->whereBetween('income_date', [$startDate, $endDate]);

        if ($containerNumber) {
            // Filter by container - join with shipments table
            $externalIncomeQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }

        $externalIncomeGhs = $externalIncomeQuery->sum('amount_ghs');
        $externalIncomeUsd = $externalIncomeQuery->sum('amount_usd');

        // Total Income in GHS
        $totalIncomeGhs = $shipmentIncomeGhs + $externalIncomeGhs;

        // 3. Expenses (all expenses including shipment-related)
        $expenseQuery = Expense::whereBetween('expense_date', [$startDate, $endDate]);

        if ($containerNumber) {
            // Filter by container - join with shipments table
            $expenseQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }

        $totalExpenses = $expenseQuery->sum('amount_ghs');

        // 4. Payroll
        $totalPayroll = PayrollEntry::whereHas('payrollPeriod', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('pay_date', [$startDate, $endDate]);
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
        $unpaidAmountGhs = $unpaidShipmentsData->sum(function ($item) {
            return ($item->total_ghs ?: 0) - ($item->paid_amount_ghs ?: 0);
        });

        // Calculate days in period for label
        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        $periodLabel = $days <= 31 ? "({$days} days)" : "(" . round($days / 30, 1) . " months)";

        // Add container filter indicator
        $filterLabel = $containerNumber ? ' [CON' . $containerNumber . ']' : '';

        return [
            Stat::make('Total Income ' . $periodLabel . $filterLabel, '₵' . number_format($totalIncomeGhs, 2))
                ->description("Shipment Payments: ₵" . number_format($shipmentIncomeGhs, 2) . " | External: ₵" . number_format($externalIncomeGhs, 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getIncomeChart($startDate, $endDate)),

            Stat::make('Total Costs ' . $periodLabel, '₵' . number_format($totalCosts, 2))
                ->description("Expenses: ₵" . number_format($totalExpenses, 2) . " | Payroll: ₵" . number_format($totalPayroll, 2))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger')
                ->chart($this->getCostChart($startDate, $endDate)),

            Stat::make('Net Profit/Loss ' . $periodLabel, '₵' . number_format($netProfit, 2))
                ->description(
                    $netProfit >= 0
                        ? 'Profitable: ' . number_format(($netProfit / ($totalIncomeGhs ?: 1)) * 100, 1) . '% margin'
                        : 'Loss: ₵' . number_format(abs($netProfit), 2)
                )
                ->descriptionIcon($netProfit >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Unpaid Shipments', $unpaidShipments)
                ->description('Outstanding: ₵' . number_format($unpaidAmountGhs, 2))
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

    protected function getIncomeChart($startDate, $endDate): array
    {
        // Dynamic chart based on date range
        $data = [];
        $exchangeService = app(ExchangeRateService::class);
        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;

        // Show last 7 data points
        $interval = max(1, floor($days / 7));

        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::parse($endDate)->subDays($i * $interval)->startOfDay();

            // Skip if date is before start date
            if ($date->lt(\Carbon\Carbon::parse($startDate))) {
                continue;
            }

            // Shipment payments for this day/period
            $shipmentPayments = Payment::where('payment_type', 'credit')
                ->whereDate('paid_on', $date)
                ->get();

            $shipmentIncomeGhs = $shipmentPayments->sum(function ($payment) use ($exchangeService) {
                if ($payment->amount_ghs) {
                    return $payment->amount_ghs;
                }
                $usd = $payment->amount_usd ?? $payment->amount ?? 0;
                return $usd * $exchangeService->getCurrentRate('USD', 'GHS');
            });

            // External income
            $externalIncome = Income::where('status', IncomeStatus::Received)
                ->whereDate('income_date', $date)
                ->sum('amount_ghs');

            $data[] = $shipmentIncomeGhs + $externalIncome;
        }
        return $data;
    }

    protected function getCostChart($startDate, $endDate): array
    {
        // Dynamic chart based on date range
        $data = [];
        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;

        // Show last 7 data points
        $interval = max(1, floor($days / 7));

        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::parse($endDate)->subDays($i * $interval)->startOfDay();

            // Skip if date is before start date
            if ($date->lt(\Carbon\Carbon::parse($startDate))) {
                continue;
            }

            $expenses = Expense::whereDate('expense_date', $date)->sum('amount_ghs');
            $payroll = PayrollEntry::whereHas('payrollPeriod', function ($query) use ($date) {
                $query->whereDate('pay_date', $date);
            })->sum('net_salary');
            $data[] = $expenses + $payroll;
        }
        return $data;
    }
}

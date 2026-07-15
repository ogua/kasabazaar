<?php

namespace App\Filament\Widgets;

use App\Enums\IncomeStatus;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Payment;
use App\Models\PayrollEntry;
use App\Models\Shipment;
use App\Service\ExchangeRateService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?string $containerNumber = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->containerNumber = Shipment::whereNotNull('container_number')->max('container_number');
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

        // When a container is selected, filter by container only (no date constraint).
        // When no container, filter by date range.
        $byContainer = (bool) $containerNumber;

        // 1. Shipment Income
        $shipmentPaymentsQuery = Payment::where('payment_type', 'credit');
        if ($byContainer) {
            $shipmentPaymentsQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        } else {
            $shipmentPaymentsQuery->whereBetween('paid_on', [$startDate, $endDate]);
        }

        $shipmentPayments = $shipmentPaymentsQuery->get();

        $shipmentIncomeUsd = $shipmentPayments->sum(function ($payment) {
            return $payment->amount_usd ?? $payment->amount ?? 0;
        });

        $shipmentIncomeGhs = $shipmentPayments->sum(function ($payment) use ($currentRate) {
            if ($payment->amount_ghs) {
                return $payment->amount_ghs;
            }
            $usd = $payment->amount_usd ?? $payment->amount ?? 0;

            return $usd * $currentRate;
        });

        // 2. External Income
        $externalIncomeQuery = Income::where('status', IncomeStatus::Received);
        if ($byContainer) {
            $externalIncomeQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        } else {
            $externalIncomeQuery->whereBetween('income_date', [$startDate, $endDate]);
        }

        $externalIncomeGhs = $externalIncomeQuery->sum('amount_ghs');
        $externalIncomeUsd = $externalIncomeQuery->sum('amount_usd');

        $totalIncomeGhs = $shipmentIncomeGhs + $externalIncomeGhs;
        $totalIncomeUsd = $shipmentIncomeUsd + $externalIncomeUsd;

        // 3. Expenses
        $expenseQuery = Expense::query();
        if ($byContainer) {
            $expenseQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        } else {
            $expenseQuery->whereBetween('expense_date', [$startDate, $endDate]);
        }

        $totalExpenses = $expenseQuery->sum('amount_ghs');

        // 4. Payroll (always date-based; not tied to containers)
        $payrollQuery = PayrollEntry::query();
        if (! $byContainer) {
            $payrollQuery->whereHas('payrollPeriod', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('pay_date', [$startDate, $endDate]);
            });
        }
        $totalPayroll = $byContainer ? 0 : $payrollQuery->sum('net_salary');

        $totalCosts = $totalExpenses + $totalPayroll;
        $totalCostsUsd = $currentRate > 0 ? $totalCosts / $currentRate : 0;

        $netProfit = $totalIncomeGhs - $totalCosts;
        $netProfitUsd = $totalIncomeUsd - $totalCostsUsd;

        // 5. Unpaid Shipments (scoped to container if selected)
        $unpaidQuery = DB::table('shipments')
            ->leftJoin('payments', function ($join) {
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
            ->havingRaw('COALESCE(paid_amount_usd, 0) < shipments.total');

        if ($byContainer) {
            $unpaidQuery->where('shipments.container_number', $containerNumber);
        }

        $unpaidShipmentsData = $unpaidQuery->get();
        $unpaidShipments = $unpaidShipmentsData->count();
        $unpaidAmountUsd = $unpaidShipmentsData->sum(function ($item) {
            return $item->total - ($item->paid_amount_usd ?: 0);
        });

        // Labels
        if ($byContainer) {
            $periodLabel = '(CON'.$containerNumber.')';
        } else {
            $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
            $periodLabel = $days <= 31 ? "({$days} days)" : '('.round($days / 30, 1).' months)';
        }

        return [
            Stat::make('Total Income '.$periodLabel, '$'.number_format($totalIncomeUsd, 2))
                ->description('Shipment Payments: $'.number_format($shipmentIncomeUsd, 2).' | External: $'.number_format($externalIncomeUsd, 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($byContainer ? [] : $this->getIncomeChart($startDate, $endDate)),

            Stat::make('Total Costs '.$periodLabel, '$'.number_format($totalCostsUsd, 2))
                ->description('Expenses: $'.number_format($currentRate > 0 ? $totalExpenses / $currentRate : 0, 2).($byContainer ? '' : ' | Payroll: $'.number_format($currentRate > 0 ? $totalPayroll / $currentRate : 0, 2)))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger')
                ->chart($byContainer ? [] : $this->getCostChart($startDate, $endDate)),

            Stat::make('Net Profit/Loss '.$periodLabel, '$'.number_format($netProfitUsd, 2))
                ->description(
                    $netProfitUsd >= 0
                        ? 'Profitable: '.number_format(($netProfitUsd / ($totalIncomeUsd ?: 1)) * 100, 1).'% margin'
                        : 'Loss: $'.number_format(abs($netProfitUsd), 2)
                )
                ->descriptionIcon($netProfit >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Unpaid Shipments', $unpaidShipments)
                ->description('Outstanding: $'.number_format($unpaidAmountUsd, 2))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($unpaidShipments > 0 ? 'warning' : 'success'),
            // ->url(route('filament.admin.resources.shipments.index', [
            //     'tenant' => \Filament\Facades\Filament::getTenant(),
            //     'tableFilters' => ['payment_status' => ['value' => 'unpaid']]
            // ])),

            Stat::make('Current Exchange Rate', '$1 = ₵'.number_format($currentRate, 2))
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

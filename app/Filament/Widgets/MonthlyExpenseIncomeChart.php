<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Payment;
use App\Enums\IncomeStatus;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Livewire\Attributes\On;

class MonthlyExpenseIncomeChart extends ChartWidget
{
    protected static ?string $heading = 'Expenses vs Income (Shipment Payments + External)';

    protected static ?int $sort = 8;

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

    protected function getData(): array
    {
        $start = \Carbon\Carbon::parse($this->startDate ?? now()->startOfMonth());
        $end = \Carbon\Carbon::parse($this->endDate ?? now());
        $containerNumber = $this->containerNumber;

        // Calculate if we should use perMonth or perDay based on date range
        $days = $start->diffInDays($end);
        $usePerMonth = $days > 60; // Use monthly if more than 2 months

        // Build base queries with container filter if applicable
        $expenseQueryBuilder = Expense::query();
        if ($containerNumber) {
            $expenseQueryBuilder->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }

        $expensesQuery = Trend::query($expenseQueryBuilder)
            ->dateColumn('expense_date')
            ->between(start: $start, end: $end);

        $expenses = $usePerMonth ? $expensesQuery->perMonth()->sum('amount_ghs') : $expensesQuery->perDay()->sum('amount_ghs');

        $incomeQueryBuilder = Income::query()
            ->where('status', IncomeStatus::Received)
            ->whereNotNull('amount_ghs');

        if ($containerNumber) {
            $incomeQueryBuilder->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }

        $externalIncomesQuery = Trend::query($incomeQueryBuilder)
            ->dateColumn('income_date')
            ->between(start: $start, end: $end);

        $externalIncomes = $usePerMonth ? $externalIncomesQuery->perMonth()->sum('amount_ghs') : $externalIncomesQuery->perDay()->sum('amount_ghs');

        $paymentQueryBuilder = Payment::query()
            ->where('payment_type', 'credit')
            ->whereNotNull('amount_ghs');

        if ($containerNumber) {
            $paymentQueryBuilder->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }

        $shipmentPaymentsQuery = Trend::query($paymentQueryBuilder)
            ->dateColumn('paid_on')
            ->between(start: $start, end: $end);

        $shipmentPayments = $usePerMonth ? $shipmentPaymentsQuery->perMonth()->sum('amount_ghs') : $shipmentPaymentsQuery->perDay()->sum('amount_ghs');

        // Combine shipment payments and external income for total income
        $totalIncome = $shipmentPayments->map(function (TrendValue $value, $index) use ($externalIncomes) {
            $externalAmount = $externalIncomes[$index]->aggregate ?? 0;
            return $value->aggregate + $externalAmount;
        });

        return [
            'datasets' => [
                [
                    'label' => 'Expenses (GHS)',
                    'data' => $expenses->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
                [
                    'label' => 'Shipment Payments (GHS)',
                    'data' => $shipmentPayments->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'External Income (GHS)',
                    'data' => $externalIncomes->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                ],
                [
                    'label' => 'Total Income (GHS)',
                    'data' => $totalIncome,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $expenses->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

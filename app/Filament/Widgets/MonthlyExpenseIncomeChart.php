<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Income;
use App\Enums\IncomeStatus;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class MonthlyExpenseIncomeChart extends ChartWidget
{
    protected static ?string $heading = 'Expenses vs External Income';

    protected static ?int $sort = 8;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $expenses = Trend::model(Expense::class)
            ->dateColumn('expense_date')
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->sum('amount_usd');

        $incomes = Trend::query(Income::query()->where('status', IncomeStatus::Received))
            ->dateColumn('income_date')
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->sum('amount_usd');

        return [
            'datasets' => [
                [
                    'label' => 'Expenses (USD)',
                    'data' => $expenses->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
                [
                    'label' => 'External Income (USD)',
                    'data' => $incomes->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
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

<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExpensesByCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Expenses by Category';

    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = Expense::select('expense_category_id', DB::raw('SUM(amount_usd) as total'))
            ->with('category')
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $colors = [
            'rgb(59, 130, 246)',   // blue
            'rgb(16, 185, 129)',   // green
            'rgb(245, 158, 11)',   // amber
            'rgb(239, 68, 68)',    // red
            'rgb(139, 92, 246)',   // violet
            'rgb(236, 72, 153)',   // pink
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Expenses (USD)',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                ],
            ],
            'labels' => $data->map(fn ($item) => $item->category?->name ?? 'Unknown')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

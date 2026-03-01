<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class ExpensesByCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Expenses by Category';

    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 1;

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

    protected function getData(): array
    {
        $startDate = $this->startDate ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->endDate ?? now()->format('Y-m-d');
        $containerNumber = $this->containerNumber;

        $query = Expense::select('expense_category_id', DB::raw('SUM(amount_usd) as total'))
            ->with('category')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->limit(6);

        if ($containerNumber) {
            $query->whereHas('shipment', function ($q) use ($containerNumber) {
                $q->where('container_number', $containerNumber);
            });
        }

        $data = $query->get();

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

<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class ExpenseStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

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

        // Current period expenses
        $currentExpensesQuery = Expense::whereBetween('expense_date', [$startDate, $endDate]);
        if ($containerNumber) {
            $currentExpensesQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }
        $thisMonthExpenses = $currentExpensesQuery->sum('amount_usd');

        // Previous period expenses
        $previousExpensesQuery = Expense::whereBetween('expense_date', [$prevStartDate, $prevEndDate]);
        if ($containerNumber) {
            $previousExpensesQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }
        $lastMonthExpenses = $previousExpensesQuery->sum('amount_usd');

        $change = $lastMonthExpenses > 0
            ? (($thisMonthExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100
            : 0;

        // Top category for the period
        $topCategoryQuery = Expense::select('expense_category_id', DB::raw('SUM(amount_usd) as total'))
            ->with('category')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->groupBy('expense_category_id')
            ->orderByDesc('total');

        if ($containerNumber) {
            $topCategoryQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }

        $topCategory = $topCategoryQuery->first();

        // GHS expenses for the period
        $currentExpensesGhsQuery = Expense::whereBetween('expense_date', [$startDate, $endDate]);
        if ($containerNumber) {
            $currentExpensesGhsQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }
        $thisMonthExpensesGhs = $currentExpensesGhsQuery->sum('amount_ghs');

        // Calculate days in period for label
        $periodLabel = $days <= 31 ? "({$days} days)" : "(" . round($days / 30, 1) . " months)";
        $filterLabel = $containerNumber ? ' [CON' . $containerNumber . ']' : '';

        return [
            Stat::make('Expenses (USD) ' . $periodLabel . $filterLabel, '$' . number_format($thisMonthExpenses, 2))
                ->description($change >= 0 ? abs(round($change, 1)) . '% increase' : abs(round($change, 1)) . '% decrease')
                ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($change >= 0 ? 'danger' : 'success'),

            Stat::make('Expenses (GHS) ' . $periodLabel, '₵' . number_format($thisMonthExpensesGhs, 2))
                ->description('Local currency total')
                ->descriptionIcon('heroicon-m-currency-dollar'),

            Stat::make('Top Category', $topCategory?->category?->name ?? 'N/A')
                ->description($topCategory ? '$' . number_format($topCategory->total, 2) : '')
                ->descriptionIcon('heroicon-m-chart-pie'),
        ];
    }
}

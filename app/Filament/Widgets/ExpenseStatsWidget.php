<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ExpenseStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $thisMonthExpenses = Expense::where('expense_date', '>=', $thisMonth)->sum('amount_usd');
        $lastMonthExpenses = Expense::where('expense_date', '>=', $lastMonth)
            ->where('expense_date', '<', $thisMonth)
            ->sum('amount_usd');

        $change = $lastMonthExpenses > 0
            ? (($thisMonthExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100
            : 0;

        $topCategory = Expense::select('expense_category_id', DB::raw('SUM(amount_usd) as total'))
            ->with('category')
            ->where('expense_date', '>=', $thisMonth)
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->first();

        return [
            Stat::make('Monthly Expenses (USD)', '$' . number_format($thisMonthExpenses, 2))
                ->description($change >= 0 ? abs(round($change, 1)) . '% increase' : abs(round($change, 1)) . '% decrease')
                ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($change >= 0 ? 'danger' : 'success'),

            Stat::make('Monthly Expenses (GHS)', '₵' . number_format(Expense::where('expense_date', '>=', $thisMonth)->sum('amount_ghs'), 2))
                ->description('Local currency total')
                ->descriptionIcon('heroicon-m-currency-dollar'),

            Stat::make('Top Category', $topCategory?->category?->name ?? 'N/A')
                ->description($topCategory ? '$' . number_format($topCategory->total, 2) : '')
                ->descriptionIcon('heroicon-m-chart-pie'),
        ];
    }
}

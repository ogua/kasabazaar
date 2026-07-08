<?php

namespace App\Service;

use App\Enums\IncomeStatus;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every method here is a standalone, purpose-built aggregate query for the
 * investor portal. Deliberately never reuse the staff-facing ReportService or
 * raw Income/Expense/Shipment collections — those carry client names,
 * shipment-level detail, and staff attribution that investors must not see.
 */
class InvestorCompanyPerformanceService
{
    /**
     * @return Collection<int, array{month: string, shipment_count: int, revenue_usd: float}>
     */
    public function monthlyRevenueTrend(int $months = 24): Collection
    {
        return Shipment::query()
            ->select([
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as shipment_count'),
                DB::raw('SUM(total) as revenue_usd'),
            ])
            ->where('created_at', '>=', now()->subMonths($months)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'shipment_count' => (int) $row->shipment_count,
                'revenue_usd' => round((float) $row->revenue_usd, 2),
            ]);
    }

    /**
     * @return array{shipment_count: int, total_revenue_usd: float, average_value_usd: float, by_month: Collection, by_status: Collection, start_date: string, end_date: string}
     */
    public function shipmentSummary(Carbon $start, Carbon $end): array
    {
        $base = Shipment::whereBetween('created_at', [$start, $end]);
        $count = (clone $base)->count();
        $totalRevenue = (float) (clone $base)->sum('total');

        $byMonth = Shipment::whereBetween('created_at', [$start, $end])
            ->select([
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as shipment_count'),
                DB::raw('SUM(total) as revenue_usd'),
            ])
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'shipment_count' => (int) $row->shipment_count,
                'revenue_usd' => round((float) $row->revenue_usd, 2),
            ]);

        $byStatus = Shipment::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status?->getLabel() ?? (string) $row->status,
                'count' => (int) $row->count,
            ]);

        return [
            'shipment_count' => $count,
            'total_revenue_usd' => round($totalRevenue, 2),
            'average_value_usd' => $count > 0 ? round($totalRevenue / $count, 2) : 0.0,
            'by_month' => $byMonth,
            'by_status' => $byStatus,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }

    /**
     * @return array{total_usd: float, total_ghs: float, count: int, by_category: Collection, start_date: string, end_date: string}
     */
    public function incomeSummary(Carbon $start, Carbon $end): array
    {
        $base = Income::whereBetween('income_date', [$start, $end])
            ->where('status', IncomeStatus::Received->value);

        $totalUsd = (float) (clone $base)->sum('amount_usd');
        $totalGhs = (float) (clone $base)->sum('amount_ghs');
        $count = (clone $base)->count();

        $byCategory = Income::whereBetween('income_date', [$start, $end])
            ->where('status', IncomeStatus::Received->value)
            ->leftJoin('income_categories', 'income_categories.id', '=', 'incomes.income_category_id')
            ->select([
                DB::raw("COALESCE(income_categories.name, 'Uncategorized') as category"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(incomes.amount_usd) as total_usd'),
                DB::raw('SUM(incomes.amount_ghs) as total_ghs'),
            ])
            ->groupBy('category')
            ->orderByDesc('total_ghs')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'count' => (int) $row->count,
                'total_usd' => round((float) $row->total_usd, 2),
                'total_ghs' => round((float) $row->total_ghs, 2),
            ]);

        return [
            'total_usd' => round($totalUsd, 2),
            'total_ghs' => round($totalGhs, 2),
            'count' => $count,
            'by_category' => $byCategory,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }

    /**
     * @return array{total_usd: float, total_ghs: float, count: int, by_category: Collection, start_date: string, end_date: string}
     */
    public function expenseSummary(Carbon $start, Carbon $end): array
    {
        $base = Expense::whereBetween('expense_date', [$start, $end]);

        $totalUsd = (float) (clone $base)->sum('amount_usd');
        $totalGhs = (float) (clone $base)->sum('amount_ghs');
        $count = (clone $base)->count();

        $byCategory = Expense::whereBetween('expense_date', [$start, $end])
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->select([
                DB::raw("COALESCE(expense_categories.name, 'Uncategorized') as category"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(expenses.amount_usd) as total_usd'),
                DB::raw('SUM(expenses.amount_ghs) as total_ghs'),
            ])
            ->groupBy('category')
            ->orderByDesc('total_ghs')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'count' => (int) $row->count,
                'total_usd' => round((float) $row->total_usd, 2),
                'total_ghs' => round((float) $row->total_ghs, 2),
            ]);

        return [
            'total_usd' => round($totalUsd, 2),
            'total_ghs' => round($totalGhs, 2),
            'count' => $count,
            'by_category' => $byCategory,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }
}

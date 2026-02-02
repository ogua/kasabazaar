<?php

namespace App\Service;

use App\Models\Expense;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExpenseService
{
    protected ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Create expense with auto currency conversion
     */
    public function createExpense(Shipment $shipment, array $data): Expense
    {
        // Get current exchange rate if not provided
        if (empty($data['exchange_rate'])) {
            $data['exchange_rate'] = $this->exchangeRateService->getCurrentRate();
        }

        // Calculate GHS amount
        $data['amount_ghs'] = $this->exchangeRateService->convertWithRate(
            $data['amount_usd'],
            $data['exchange_rate']
        );

        $data['shipment_id'] = $shipment->id;
        $data['branch_id'] = $shipment->branch_id;

        return Expense::create($data);
    }

    /**
     * Get expense summary for a shipment
     */
    public function getShipmentExpenseSummary(Shipment $shipment): array
    {
        $expenses = $shipment->expenses()->with('category')->get();

        return [
            'total_usd' => $expenses->sum('amount_usd'),
            'total_ghs' => $expenses->sum('amount_ghs'),
            'count' => $expenses->count(),
            'by_category' => $expenses->groupBy('category.name')->map(fn ($items) => [
                'count' => $items->count(),
                'total_usd' => $items->sum('amount_usd'),
                'total_ghs' => $items->sum('amount_ghs'),
            ]),
            'by_stage' => $expenses->groupBy('expense_stage')->map(fn ($items) => [
                'count' => $items->count(),
                'total_usd' => $items->sum('amount_usd'),
                'total_ghs' => $items->sum('amount_ghs'),
            ]),
            'expenses' => $expenses,
        ];
    }

    /**
     * Get expense summary by category for a period
     */
    public function getExpensesByCategory(Carbon $startDate, Carbon $endDate, ?string $branchId = null): Collection
    {
        $query = Expense::with('category')
            ->whereBetween('expense_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()
            ->groupBy('category.name')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'total_usd' => $items->sum('amount_usd'),
                'total_ghs' => $items->sum('amount_ghs'),
            ]);
    }

    /**
     * Get monthly expense trend
     */
    public function getMonthlyExpenseTrend(int $year, ?string $branchId = null): array
    {
        $query = Expense::whereYear('expense_date', $year);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $expenses = $query->get();

        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthExpenses = $expenses->filter(fn ($e) => $e->expense_date->month === $month);
            $monthlyData[$month] = [
                'month' => Carbon::create($year, $month)->format('M'),
                'total_usd' => $monthExpenses->sum('amount_usd'),
                'total_ghs' => $monthExpenses->sum('amount_ghs'),
                'count' => $monthExpenses->count(),
            ];
        }

        return $monthlyData;
    }

    /**
     * Get total expenses for a period
     */
    public function getTotalExpenses(Carbon $startDate, Carbon $endDate, ?string $branchId = null): array
    {
        $query = Expense::whereBetween('expense_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $expenses = $query->get();

        return [
            'total_usd' => $expenses->sum('amount_usd'),
            'total_ghs' => $expenses->sum('amount_ghs'),
            'count' => $expenses->count(),
        ];
    }
}

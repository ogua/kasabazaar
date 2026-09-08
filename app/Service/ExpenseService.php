<?php

namespace App\Service;

use App\Enums\ExpenseScope;
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
     * Create a shipment-scoped expense with auto currency conversion.
     */
    public function createExpense(Shipment $shipment, array $data): Expense
    {
        $data = $this->withConvertedAmount($data);
        $data['expense_for'] = ExpenseScope::Shipment;
        $data['shipment_id'] = $shipment->id;
        $data['container_number'] = null;
        $data['branch_id'] = $shipment->branch_id;

        return Expense::create($data);
    }

    /**
     * Create a container-scoped expense (not tied to a single shipment).
     */
    public function createContainerExpense(string $containerNumber, array $data, ?string $branchId = null): Expense
    {
        $data = $this->withConvertedAmount($data);
        $data['expense_for'] = ExpenseScope::Container;
        $data['container_number'] = $containerNumber;
        $data['shipment_id'] = null;
        $data['branch_id'] = $branchId
            ?? Shipment::where('container_number', $containerNumber)->value('branch_id');

        return Expense::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withConvertedAmount(array $data): array
    {
        if (empty($data['exchange_rate'])) {
            $data['exchange_rate'] = $this->exchangeRateService->getCurrentRate();
        }

        $rate = (float) $data['exchange_rate'];

        if (empty($data['amount_usd']) && ! empty($data['amount_ghs']) && $rate > 0) {
            $data['amount_usd'] = round((float) $data['amount_ghs'] / $rate, 2);
        }

        $data['amount_ghs'] = $this->exchangeRateService->convertWithRate(
            $data['amount_usd'] ?? 0,
            $data['exchange_rate']
        );

        return $data;
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
     * Get the expense summary for a whole container: expenses booked directly
     * against the container plus every expense on its member shipments.
     */
    public function getContainerExpenseSummary(string $containerNumber): array
    {
        $expenses = Expense::forContainer($containerNumber)->with('category')->get();

        return [
            'container_number' => $containerNumber,
            'container_ref' => 'CON'.$containerNumber,
            'total_usd' => $expenses->sum('amount_usd'),
            'total_ghs' => $expenses->sum('amount_ghs'),
            'count' => $expenses->count(),
            'direct_total_usd' => $expenses->where('expense_for', ExpenseScope::Container)->sum('amount_usd'),
            'shipment_total_usd' => $expenses->where('expense_for', ExpenseScope::Shipment)->sum('amount_usd'),
            'by_category' => $expenses->groupBy('category.name')->map(fn ($items) => [
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

<?php

namespace App\Service;

use App\Models\Income;
use App\Enums\IncomeStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class IncomeService
{
    protected ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Create income with auto currency conversion
     */
    public function createIncome(array $data): Income
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

        return Income::create($data);
    }

    /**
     * Get income summary by category for a period
     */
    public function getIncomeByCategory(Carbon $startDate, Carbon $endDate, ?string $branchId = null): Collection
    {
        $query = Income::with('category')
            ->where('status', IncomeStatus::Received)
            ->whereBetween('income_date', [$startDate, $endDate]);

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
     * Get total income for a period
     */
    public function getTotalIncome(Carbon $startDate, Carbon $endDate, ?string $branchId = null): array
    {
        $query = Income::where('status', IncomeStatus::Received)
            ->whereBetween('income_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $incomes = $query->get();

        return [
            'total_usd' => $incomes->sum('amount_usd'),
            'total_ghs' => $incomes->sum('amount_ghs'),
            'count' => $incomes->count(),
        ];
    }

    /**
     * Get monthly income trend
     */
    public function getMonthlyIncomeTrend(int $year, ?string $branchId = null): array
    {
        $query = Income::where('status', IncomeStatus::Received)
            ->whereYear('income_date', $year);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $incomes = $query->get();

        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthIncomes = $incomes->filter(fn ($i) => $i->income_date->month === $month);
            $monthlyData[$month] = [
                'month' => Carbon::create($year, $month)->format('M'),
                'total_usd' => $monthIncomes->sum('amount_usd'),
                'total_ghs' => $monthIncomes->sum('amount_ghs'),
                'count' => $monthIncomes->count(),
            ];
        }

        return $monthlyData;
    }

    /**
     * Get income by payment method
     */
    public function getIncomeByPaymentMethod(Carbon $startDate, Carbon $endDate, ?string $branchId = null): Collection
    {
        $query = Income::where('status', IncomeStatus::Received)
            ->whereBetween('income_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()
            ->groupBy('payment_method')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'total_usd' => $items->sum('amount_usd'),
                'total_ghs' => $items->sum('amount_ghs'),
            ]);
    }
}

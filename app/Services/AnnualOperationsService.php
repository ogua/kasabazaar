<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ShipmentItem;

/**
 * Year-to-date operating figures on the admin dashboard: vehicles shipped (with
 * this month and the change on last year) and demurrage paid. Shared by
 * AnnualOperationsWidget and the staff app's dashboard API.
 */
class AnnualOperationsService
{
    /**
     * @return array{year: int, cars_this_year: int, cars_last_year: int, cars_this_month: int, cars_change_percent: ?float, demurrage_ghs: float, demurrage_usd: float}
     */
    public function compute(?int $year = null): array
    {
        $year ??= now()->year;

        $carsThisYear = $this->vehiclesShipped($year);
        $carsLastYear = $this->vehiclesShipped($year - 1);

        return [
            'year' => $year,
            'cars_this_year' => $carsThisYear,
            'cars_last_year' => $carsLastYear,
            'cars_this_month' => (int) ShipmentItem::vehicles()
                ->whereHas('shipment', fn ($q) => $q->whereYear('created_at', $year)->whereMonth('created_at', now()->month))
                ->sum('quantity'),
            'cars_change_percent' => $carsLastYear > 0
                ? round((($carsThisYear - $carsLastYear) / $carsLastYear) * 100, 1)
                : null,
            'demurrage_ghs' => $this->demurrage($year, 'amount_ghs'),
            'demurrage_usd' => $this->demurrage($year, 'amount_usd'),
        ];
    }

    private function vehiclesShipped(int $year): int
    {
        return (int) ShipmentItem::vehicles()
            ->whereHas('shipment', fn ($q) => $q->whereYear('created_at', $year))
            ->sum('quantity');
    }

    private function demurrage(int $year, string $column): float
    {
        return round((float) Expense::query()
            ->whereHas('category', fn ($q) => $q->where('code', 'DEMURRAGE'))
            ->whereYear('expense_date', $year)
            ->sum($column), 2);
    }
}

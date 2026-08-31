<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\ShipmentItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnnualOperationsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $year = now()->year;

        $carsThisYear = $this->vehiclesShipped($year);
        $carsLastYear = $this->vehiclesShipped($year - 1);
        $carsThisMonth = (int) ShipmentItem::vehicles()
            ->whereHas('shipment', fn ($q) => $q->whereYear('created_at', $year)->whereMonth('created_at', now()->month))
            ->sum('quantity');

        $carsChange = $carsLastYear > 0
            ? round((($carsThisYear - $carsLastYear) / $carsLastYear) * 100, 1)
            : null;

        $demurrageGhs = $this->demurrage($year, 'amount_ghs');
        $demurrageUsd = $this->demurrage($year, 'amount_usd');

        return [
            Stat::make('Cars Shipped ('.$year.')', number_format($carsThisYear))
                ->description(
                    $carsChange === null
                        ? $carsThisMonth.' this month'
                        : $carsThisMonth.' this month · '.($carsChange >= 0 ? '+' : '').$carsChange.'% vs '.($year - 1)
                )
                ->descriptionIcon($carsChange !== null && $carsChange < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($carsChange !== null && $carsChange < 0 ? 'danger' : 'success'),

            Stat::make('Demurrage Paid YTD (GHS)', 'GHS '.number_format($demurrageGhs, 2))
                ->description('Shipping-line demurrage & detention, '.$year)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Demurrage Paid YTD (USD)', '$'.number_format($demurrageUsd, 2))
                ->description('Year to date')
                ->descriptionIcon('heroicon-m-calendar'),
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
        return (float) Expense::query()
            ->whereHas('category', fn ($q) => $q->where('code', 'DEMURRAGE'))
            ->whereYear('expense_date', $year)
            ->sum($column);
    }
}

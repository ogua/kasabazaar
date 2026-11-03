<?php

namespace App\Filament\Widgets;

use App\Services\AnnualOperationsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnnualOperationsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $ops = app(AnnualOperationsService::class)->compute();
        $year = $ops['year'];
        $carsThisYear = $ops['cars_this_year'];
        $carsThisMonth = $ops['cars_this_month'];
        $carsChange = $ops['cars_change_percent'];
        $demurrageGhs = $ops['demurrage_ghs'];
        $demurrageUsd = $ops['demurrage_usd'];

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
}

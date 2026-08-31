<?php

namespace App\Filament\Widgets;

use App\Enums\ShippingStatus;
use App\Models\Shipment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStatistics extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $shipping = Shipment::count();
        $delivery = Shipment::where('status', ShippingStatus::delivered->value)->count();
        $pending = Shipment::where('status', ShippingStatus::pending->value)->count();
        $transit = Shipment::where('status', ShippingStatus::shipped->value)->count();

        return [
            Stat::make('Total Shipments', '')
                ->description($shipping)
                ->descriptionIcon('heroicon-m-truck')
                ->color('success')
                ->extraAttributes([
                    'class' => 'bg-primary-500',
                ])
                ->chartColor('info'),

            Stat::make('Total Delivered', '')
                ->description($delivery)
                ->descriptionIcon('heroicon-m-truck')
                ->color('success'),

            Stat::make('Total Pending', '')
                ->description($pending)
                ->descriptionIcon('heroicon-m-truck')
                ->color('danger'),

            Stat::make('Total in transit', '')
                ->description($transit)
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

        ];
    }
}

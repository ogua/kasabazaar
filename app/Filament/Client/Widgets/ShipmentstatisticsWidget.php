<?php

namespace App\Filament\Client\Widgets;

use App\Models\Shipment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShipmentstatisticsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $shipping = Shipment::where('client_id', auth()->user()->client_id)->count();
        $delivery = Shipment::where('client_id', auth()->user()->client_id)->whereIn('status', \App\Enums\ShippingStatus::completedValues())->count();
        $pending = Shipment::where('client_id', auth()->user()->client_id)->whereIn('status', \App\Enums\ShippingStatus::awaitingDispatchValues())->count();
        $transit = Shipment::where('client_id', auth()->user()->client_id)->whereIn('status', \App\Enums\ShippingStatus::inTransitValues())->count();

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

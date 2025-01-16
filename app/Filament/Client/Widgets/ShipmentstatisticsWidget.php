<?php

namespace App\Filament\Client\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Shipment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;


class ShipmentstatisticsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $shipping = Shipment::where('client_id',auth()->user()->client_id)->count();
        $delivery = Shipment::where('client_id',auth()->user()->client_id)->where('status','delivered')->count();
        $pending = Shipment::where('client_id',auth()->user()->client_id)->where('status','pending')->count();
        $transit = Shipment::where('client_id',auth()->user()->client_id)->where('status','in transit')->count();

        return [
            Stat::make("Total Shipments","")
            ->description($shipping)
            ->descriptionIcon('heroicon-m-truck')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-primary-500',
            ])
            ->chartColor('info'),

            Stat::make("Total Delivered","")
                ->description($delivery)
                ->descriptionIcon('heroicon-m-truck')
                ->color('success'),

            Stat::make("Total Pending","")
            ->description($pending)
            ->descriptionIcon('heroicon-m-truck')
            ->color('danger'),

            Stat::make("Total in transit","")
                ->description($transit)
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
        ];
    }
}

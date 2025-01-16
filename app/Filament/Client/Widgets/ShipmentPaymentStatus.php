<?php

namespace App\Filament\Client\Widgets;

use App\Models\Shipment;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ShipmentPaymentStatus extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected function getStats(): array
    {
        return [
            Stat::make('Pending Payment', Shipment::where('payment_status', 'pending')->where('client_id',auth()->user()->client_id)->count()),
            Stat::make('Paid Shipments', Shipment::where('payment_status', 'paid')->where('client_id',auth()->user()->client_id)->count()),
            Stat::make('Partial Payments', Shipment::where('payment_status', 'partial')->where('client_id',auth()->user()->client_id)->count()),
            
        ];
    }
}

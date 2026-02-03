<?php

namespace App\Filament\Widgets;

use App\Models\Shipment;
use App\Models\Receiver;
use App\Models\State;
use App\Models\Payment;
use Filament\Widgets\Widget;

class TopStatesWidget extends Widget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static string $view = 'filament.widgets.top-states-widget';

    public function getStatesData(): array
    {
        // Get shipments grouped by state from receivers
        $statesData = Receiver::whereNotNull('state_region')
            ->with('shipment')
            ->get()
            ->groupBy('state_region')
            ->map(function ($receivers, $stateId) {
                $shipments = $receivers->pluck('shipment')->filter();
                $shipmentIds = $shipments->pluck('id');

                $paymentsReceived = Payment::where('payment_type', 'credit')
                    ->whereIn('shipment_id', $shipmentIds)
                    ->sum('amount_ghs') ?: 0;

                $totalRevenueGhs = $shipments->sum('total_ghs') ?: 1;
                $paymentRate = ($paymentsReceived / $totalRevenueGhs) * 100;

                $totalShipments = Shipment::count() ?: 1;
                $marketShare = ($shipments->count() / $totalShipments) * 100;

                $state = State::find($stateId);

                return [
                    'state_id' => $stateId,
                    'state_name' => $state ? $state->name : 'Unknown',
                    'shipment_count' => $shipments->count(),
                    'total_revenue_usd' => $shipments->sum('total'),
                    'total_revenue_ghs' => $shipments->sum('total_ghs'),
                    'avg_shipment_value_usd' => $shipments->avg('total') ?: 0,
                    'payments_received' => $paymentsReceived,
                    'payment_rate' => $paymentRate,
                    'payment_rate_class' => $paymentRate >= 80 ? 'success' : ($paymentRate >= 50 ? 'warning' : 'danger'),
                    'market_share' => $marketShare,
                ];
            })
            ->sortByDesc('shipment_count')
            ->take(10)
            ->values()
            ->toArray();

        return $statesData;
    }
}

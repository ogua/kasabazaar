<?php

namespace App\Http\Resources\Ecommerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicOrderTrackingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'items_count' => $this->items_count ?? $this->items()->count(),
            'created_at' => $this->created_at,
            'status_history' => $this->whenLoaded('statusHistory', fn () => $this->statusHistory->map(fn ($h) => [
                'status' => $h->status,
                'created_at' => $h->created_at,
            ])),
            'shipment' => $this->whenLoaded('shipment', fn () => $this->shipment ? [
                'tracking_number' => $this->shipment->tracking_number,
                'courier' => $this->shipment->courier,
                'status' => $this->shipment->status instanceof \BackedEnum ? $this->shipment->status->value : $this->shipment->status,
                'estimated_delivery' => $this->shipment->estimated_delivery,
                'dispatched_at' => $this->shipment->dispatched_at,
                'delivered_at' => $this->shipment->delivered_at,
                'tracking_logs' => $this->shipment->relationLoaded('trackingLogs')
                    ? $this->shipment->trackingLogs->map(fn ($log) => [
                        'latitude' => $log->latitude,
                        'longitude' => $log->longitude,
                        'status' => $log->status,
                        'recorded_at' => $log->recorded_at,
                    ])
                    : [],
            ] : null),
        ];
    }
}

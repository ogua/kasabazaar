<?php

namespace App\Http\Resources\Ecommerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceOrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'payment_status' => $this->payment_status instanceof \BackedEnum ? $this->payment_status->value : $this->payment_status,
            'payment_gateway' => $this->payment_gateway,
            'payment_reference' => $this->payment_reference,
            'subtotal_ghs' => $this->subtotal_ghs,
            'shipping_fee_ghs' => $this->shipping_fee_ghs,
            'discount_ghs' => $this->discount_ghs,
            'total_ghs' => $this->total_ghs,
            'total_usd' => $this->total_usd,
            'exchange_rate' => $this->exchange_rate,
            'notes' => $this->notes,
            'cancelled_reason' => $this->cancelled_reason,
            'created_at' => $this->created_at,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->ecommerce_product_id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'quantity' => $item->quantity,
                'unit_price_ghs' => $item->unit_price_ghs,
                'total_ghs' => $item->total_ghs,
                'product' => $item->product ? new EcommerceProductResource($item->product) : null,
            ])),
            'delivery_detail' => $this->whenLoaded('deliveryDetail', fn () => $this->deliveryDetail ? [
                'full_name' => $this->deliveryDetail->full_name,
                'phone' => $this->deliveryDetail->phone,
                'alternative_phone' => $this->deliveryDetail->alternative_phone,
                'email' => $this->deliveryDetail->email,
                'country' => $this->deliveryDetail->country,
                'region' => $this->deliveryDetail->region,
                'city' => $this->deliveryDetail->city,
                'suburb' => $this->deliveryDetail->suburb,
                'street' => $this->deliveryDetail->street,
                'house_number' => $this->deliveryDetail->house_number,
                'digital_address' => $this->deliveryDetail->digital_address,
                'landmark' => $this->deliveryDetail->landmark,
                'delivery_notes' => $this->deliveryDetail->delivery_notes,
            ] : null),
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
            'status_history' => $this->whenLoaded('statusHistory', fn () => $this->statusHistory->map(fn ($h) => [
                'status' => $h->status,
                'notes' => $h->notes,
                'created_at' => $h->created_at,
            ])),
            'rating' => $this->whenLoaded('rating', fn () => $this->rating ? [
                'rating' => $this->rating->rating,
                'comment' => $this->rating->comment,
                'created_at' => $this->rating->created_at,
            ] : null),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
        ];
    }
}

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
            'order_group_id' => $this->order_group_id,
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'id' => $this->vendor->id,
                'business_name' => $this->vendor->business_name,
                'slug' => $this->vendor->slug,
            ] : null),
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'payment_status' => $this->payment_status instanceof \BackedEnum ? $this->payment_status->value : $this->payment_status,
            'payment_gateway' => $this->payment_gateway,
            'payment_reference' => $this->payment_reference,
            'subtotal_ghs' => $this->subtotal_ghs,
            'shipping_fee_ghs' => $this->shipping_fee_ghs,
            'delivery_method' => $this->delivery_method ?? 'delivery',
            'pickup_station' => $this->whenLoaded('pickupStation', fn () => $this->pickupStation ? [
                'id' => $this->pickupStation->id,
                'name' => $this->pickupStation->name,
                'country' => $this->pickupStation->country,
                'address' => $this->pickupStation->fullAddress(),
                'phone' => $this->pickupStation->phone,
                'opening_hours' => $this->pickupStation->opening_hours,
                'instructions' => $this->pickupStation->instructions,
            ] : null),
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'name' => $this->warehouse->name,
                'address' => $this->warehouse->fullAddress(),
            ] : null),
            'sent_to_warehouse_at' => $this->sent_to_warehouse_at,
            'received_at_warehouse_at' => $this->received_at_warehouse_at,
            'inspected_at' => $this->inspected_at,
            'inspection_result' => $this->inspection_result,
            'ready_for_pickup_at' => $this->ready_for_pickup_at,
            'returns' => $this->whenLoaded('returns', fn () => $this->returns->map(fn ($r) => [
                'id' => $r->id,
                'return_number' => $r->return_number,
                'status' => $r->status,
                'reason' => $r->reason,
                'staff_notes' => $r->staff_notes,
                'warehouse' => $r->warehouse ? ['name' => $r->warehouse->name, 'address' => $r->warehouse->fullAddress(), 'phone' => $r->warehouse->phone] : null,
                'created_at' => $r->created_at,
            ])->values()),
            'delivery_fee_status' => $this->delivery_fee_status ?? 'not_required',
            'delivery_fee_ghs' => $this->delivery_fee_ghs,
            'delivery_fee_notes' => $this->delivery_fee_notes,
            'delivery_fee_paid_at' => $this->delivery_fee_paid_at,
            'delivery_scheduled_at' => $this->delivery_scheduled_at,
            'delivery_plan_notes' => $this->delivery_plan_notes,
            'discount_ghs' => $this->discount_ghs,
            'tax_ghs' => $this->tax_ghs,
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

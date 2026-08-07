<?php

namespace App\Http\Resources\Ecommerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceOrderGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_group_number' => $this->order_group_number,
            'payment_status' => $this->payment_status instanceof \BackedEnum ? $this->payment_status->value : $this->payment_status,
            'payment_gateway' => $this->payment_gateway,
            'payment_reference' => $this->payment_reference,
            'subtotal_ghs' => $this->subtotal_ghs,
            'shipping_fee_ghs' => $this->shipping_fee_ghs,
            'discount_ghs' => $this->discount_ghs,
            'tax_ghs' => $this->tax_ghs,
            'total_ghs' => $this->total_ghs,
            'total_usd' => $this->total_usd,
            'exchange_rate' => $this->exchange_rate,
            'coupon_code' => $this->coupon_code,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'orders' => $this->whenLoaded('orders', fn () => EcommerceOrderDetailResource::collection($this->orders)),
        ];
    }
}

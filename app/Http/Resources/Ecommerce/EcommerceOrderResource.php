<?php

namespace App\Http\Resources\Ecommerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'payment_status' => $this->payment_status instanceof \BackedEnum ? $this->payment_status->value : $this->payment_status,
            'payment_gateway' => $this->payment_gateway,
            'subtotal_ghs' => $this->subtotal_ghs,
            'shipping_fee_ghs' => $this->shipping_fee_ghs,
            'discount_ghs' => $this->discount_ghs,
            'total_ghs' => $this->total_ghs,
            'total_usd' => $this->total_usd,
            'items_count' => $this->items_count ?? $this->items()->count(),
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone ?? null,
            ]),
        ];
    }
}

<?php

namespace App\Http\Resources\Ecommerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceCartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->relationLoaded('items') ? $this->resource->items : collect();
        $subtotalGhs = $items->sum(fn ($i) => $i->price_ghs * $i->quantity);

        return [
            'branch_id' => $this->branch_id,
            'coupon_code' => $this->coupon_code,
            'subtotal_ghs' => round($subtotalGhs, 2),
            'expires_at' => $this->expires_at,
            'items' => EcommerceCartItemResource::collection($items),
        ];
    }
}

<?php

namespace App\Http\Resources\Ecommerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceCartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;

        return [
            'id' => $this->id,
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'id' => $this->vendor->id,
                'business_name' => $this->vendor->business_name,
            ] : null),
            'quantity' => $this->quantity,
            'price_ghs' => $this->price_ghs,
            'stock' => $product?->stock,
            'in_stock' => $product && ! $product->trashed() && $product->stock >= $this->quantity,
            'is_available' => $product && ! $product->trashed(),
            'product' => new EcommerceProductResource($this->whenLoaded('product')),
        ];
    }
}

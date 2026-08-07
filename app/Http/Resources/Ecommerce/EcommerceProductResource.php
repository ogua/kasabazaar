<?php

namespace App\Http\Resources\Ecommerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'specifications' => $this->specifications,
            'price_ghs' => $this->price_ghs,
            'price_usd' => $this->price_usd,
            'discount_price_ghs' => $this->discount_price_ghs,
            'discount_price_usd' => $this->discount_price_usd,
            'weight' => $this->weight,
            'stock' => $this->stock,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'category' => new EcommerceCategoryResource($this->whenLoaded('category')),
            'images' => EcommerceProductImageResource::collection($this->whenLoaded('images')),
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'id' => $this->vendor->id,
                'business_name' => $this->vendor->business_name,
                'slug' => $this->vendor->slug,
            ] : null),
            'average_rating' => $this->when(method_exists($this->resource, 'averageRating'), fn () => $this->averageRating()),
            'reviews_count' => $this->when(method_exists($this->resource, 'reviewsCount'), fn () => $this->reviewsCount()),
        ];
    }
}

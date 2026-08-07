<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'price_ghs' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(EcommerceProduct::class, 'ecommerce_product_id');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(EcommerceProductImage::class, 'image_id');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttributeValue::class, 'product_variant_attribute_value');
    }

    public function label(): string
    {
        return $this->attributeValues->pluck('value')->implode(' / ');
    }

    public function effectivePriceGhs(): float
    {
        return (float) ($this->price_ghs ?? $this->product->price_ghs);
    }
}

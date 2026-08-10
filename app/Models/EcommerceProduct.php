<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EcommerceProduct extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ecommerce_products';

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'price_ghs' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'discount_price_ghs' => 'decimal:2',
            'discount_price_usd' => 'decimal:2',
            'weight' => 'decimal:3',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->slug ??= Str::slug($model->name);
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EcommerceCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(EcommerceProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(EcommerceProductImage::class)->where('is_primary', true)->limit(1);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(EcommerceInventoryLog::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(EcommerceCartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(EcommerceOrderItem::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'ecommerce_product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'ecommerce_product_id')->where('is_approved', true);
    }

    public function averageRating(): float
    {
        return round((float) $this->reviews()->avg('rating'), 1);
    }

    public function reviewsCount(): int
    {
        return $this->reviews()->count();
    }
}

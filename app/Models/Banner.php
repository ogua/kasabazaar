<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Marketplace storefront banner (homepage hero slider and promo slots),
 * served to the kmarket storefront over GET /api/v1/marketplace/banners.
 */
class Banner extends Model
{
    use HasUuids;

    public const POSITIONS = [
        'homepage_hero' => 'Homepage hero slider',
        'homepage_promo_1' => 'Homepage promo 1',
        'homepage_promo_2' => 'Homepage promo 2',
        'auctions_hero' => 'Auctions page hero',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Active and inside its optional display window.
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function imageUrl(): string
    {
        return asset('storage/'.$this->image_path);
    }
}

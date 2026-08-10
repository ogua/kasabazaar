<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount_ghs' => 'decimal:2',
            'max_discount_ghs' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValidFor(User $user, float $subtotalGhs): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->min_order_amount_ghs && $subtotalGhs < (float) $this->min_order_amount_ghs) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        if ($this->usage_limit_per_user !== null) {
            $userUsageCount = $this->usages()->where('user_id', $user->id)->count();

            if ($userUsageCount >= $this->usage_limit_per_user) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $subtotalGhs): float
    {
        $discount = $this->type === 'percentage'
            ? $subtotalGhs * ((float) $this->value / 100)
            : (float) $this->value;

        if ($this->max_discount_ghs !== null) {
            $discount = min($discount, (float) $this->max_discount_ghs);
        }

        return round(min($discount, $subtotalGhs), 2);
    }
}

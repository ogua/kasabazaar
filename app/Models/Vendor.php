<?php

namespace App\Models;

use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'status' => VendorStatus::class,
            'payout_details' => 'encrypted',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $vendor) {
            $vendor->slug ??= Str::slug($vendor->business_name);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VendorStatus::Active);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(EcommerceProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(EcommerceOrder::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(VendorWallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(VendorTransaction::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(VendorPayoutRequest::class);
    }
}

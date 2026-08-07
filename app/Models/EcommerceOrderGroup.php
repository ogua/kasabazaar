<?php

namespace App\Models;

use App\Enums\EcommerceOrderPaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcommerceOrderGroup extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ecommerce_order_groups';

    protected function casts(): array
    {
        return [
            'payment_status' => EcommerceOrderPaymentStatus::class,
            'subtotal_ghs' => 'decimal:2',
            'shipping_fee_ghs' => 'decimal:2',
            'discount_ghs' => 'decimal:2',
            'tax_ghs' => 'decimal:2',
            'total_ghs' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $group) {
            if (empty($group->order_group_number)) {
                $group->order_group_number = self::generateOrderGroupNumber();
            }
        });
    }

    public static function generateOrderGroupNumber(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now())->withTrashed()->count() + 1;

        return sprintf('KMB-GRP-%s-%04d', $date, $count);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(EcommerceOrder::class, 'order_group_id');
    }
}

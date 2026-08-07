<?php

namespace App\Models;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Enums\EcommerceOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcommerceOrder extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ecommerce_orders';

    protected function casts(): array
    {
        return [
            'status' => EcommerceOrderStatus::class,
            'payment_status' => EcommerceOrderPaymentStatus::class,
            'subtotal_ghs' => 'decimal:2',
            'shipping_fee_ghs' => 'decimal:2',
            'discount_ghs' => 'decimal:2',
            'total_ghs' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now())->withTrashed()->count() + 1;

        return sprintf('KMB-%s-%04d', $date, $count);
    }

    public function scopeForCustomer(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function orderGroup(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrderGroup::class, 'order_group_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EcommerceOrderItem::class, 'order_id');
    }

    public function deliveryDetail(): HasOne
    {
        return $this->hasOne(OrderDeliveryDetail::class, 'order_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(EcommerceOrderStatusHistory::class, 'order_id')->orderBy('created_at');
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(EcommerceOrderShipment::class, 'order_id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(EcommerceOrderRating::class, 'order_id');
    }
}

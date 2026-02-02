<?php

namespace App\Models;

use App\Enums\IncomeStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'income_date' => 'date',
        'amount_usd' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_ghs' => 'decimal:2',
        'status' => IncomeStatus::class,
        'payment_method' => PaymentMethod::class,
    ];

    protected static function booted()
    {
        static::creating(function ($income) {
            if (empty($income->reference)) {
                $income->reference = self::generateReference();
            }
            if ($income->amount_usd && $income->exchange_rate) {
                $income->amount_ghs = $income->amount_usd * $income->exchange_rate;
            }
        });

        static::updating(function ($income) {
            if ($income->amount_usd && $income->exchange_rate) {
                $income->amount_ghs = $income->amount_usd * $income->exchange_rate;
            }
        });
    }

    public static function generateReference(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now())->count() + 1;
        return sprintf('INC-%s-%04d', $date, $count);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function scopeReceived($query)
    {
        return $query->where('status', IncomeStatus::Received);
    }
}

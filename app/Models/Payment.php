<?php

namespace App\Models;

use App\Service\ExchangeRateService;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'paid_on' => 'datetime',
        'amount' => 'decimal:2',
        'amount_usd' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_ghs' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($payment) {
            // Auto-calculate exchange rate and GHS equivalent if not provided
            if ($payment->amount_usd && !$payment->exchange_rate) {
                $exchangeService = app(ExchangeRateService::class);
                $payment->exchange_rate = $exchangeService->getCurrentRate('USD', 'GHS');
            }

            if ($payment->amount_usd && $payment->exchange_rate && !$payment->amount_ghs) {
                $payment->amount_ghs = $payment->amount_usd * $payment->exchange_rate;
            }

            // Keep backward compatibility: if amount_usd not set but amount is, use amount as USD
            if (!$payment->amount_usd && $payment->amount) {
                $payment->amount_usd = $payment->amount;
                if ($payment->exchange_rate) {
                    $payment->amount_ghs = $payment->amount_usd * $payment->exchange_rate;
                }
            }
        });

        static::updating(function ($payment) {
            // Recalculate GHS if USD amount or exchange rate changes
            if ($payment->isDirty(['amount_usd', 'exchange_rate'])) {
                if ($payment->amount_usd && $payment->exchange_rate) {
                    $payment->amount_ghs = $payment->amount_usd * $payment->exchange_rate;
                }
            }

            // Keep backward compatibility
            if ($payment->isDirty('amount') && !$payment->isDirty('amount_usd')) {
                $payment->amount_usd = $payment->amount;
                if ($payment->exchange_rate) {
                    $payment->amount_ghs = $payment->amount_usd * $payment->exchange_rate;
                }
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class,"shipment_id");
    }


    public function enteredby(): BelongsTo
    {
        return $this->belongsTo(User::class,"user_id");
    }

    public function scopeOfbranch($query)
    {
        $query->where('branch_id',Filament::getTenant()->id);
    }
}

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
        'amount_ghs' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'balance' => 'decimal:2',
        'change' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($payment) {
            // Auto-calculate exchange rate if not provided
            if (! $payment->exchange_rate && ($payment->amount_usd || $payment->amount_ghs || $payment->amount)) {
                $exchangeService = app(ExchangeRateService::class);
                $payment->exchange_rate = $exchangeService->getCurrentRate('USD', 'GHS');
            }

            // GHS is the amount actually received (currency = GHS); derive USD from it.
            if ($payment->currency === 'GHS' && $payment->amount_ghs && $payment->exchange_rate && ! $payment->amount_usd) {
                $payment->amount_usd = round($payment->amount_ghs / $payment->exchange_rate, 2);
            }

            // USD is the amount actually received (default path); derive GHS from it.
            if ($payment->amount_usd && $payment->exchange_rate && ! $payment->amount_ghs) {
                $payment->amount_ghs = $payment->amount_usd * $payment->exchange_rate;
            }

            // Keep backward compatibility: if amount_usd not set but amount is, use amount as USD
            if (! $payment->amount_usd && $payment->amount) {
                $payment->amount_usd = $payment->amount;
                if ($payment->exchange_rate && ! $payment->amount_ghs) {
                    $payment->amount_ghs = $payment->amount_usd * $payment->exchange_rate;
                }
            }

            // The legacy 'amount' column always mirrors the USD figure — shipment
            // balances (Shipment::paid, payment_status) are USD-denominated.
            if ($payment->amount_usd) {
                $payment->amount = $payment->amount_usd;
            }
        });

        static::updating(function ($payment) {
            if ($payment->currency === 'GHS' && $payment->isDirty('amount_ghs') && $payment->exchange_rate) {
                // GHS is the amount actually received; recalculate USD from it.
                $payment->amount_usd = round($payment->amount_ghs / $payment->exchange_rate, 2);
            } elseif ($payment->isDirty(['amount_usd', 'exchange_rate']) && $payment->currency !== 'GHS') {
                // USD is the amount actually received; recalculate GHS from it.
                if ($payment->amount_usd && $payment->exchange_rate) {
                    $payment->amount_ghs = $payment->amount_usd * $payment->exchange_rate;
                }
            }

            // Keep backward compatibility
            if ($payment->isDirty('amount') && ! $payment->isDirty('amount_usd')) {
                $payment->amount_usd = $payment->amount;
                if ($payment->exchange_rate) {
                    $payment->amount_ghs = $payment->amount_usd * $payment->exchange_rate;
                }
            }

            if ($payment->amount_usd) {
                $payment->amount = $payment->amount_usd;
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeOfbranch($query)
    {
        $query->where('branch_id', Filament::getTenant()->id);
    }
}

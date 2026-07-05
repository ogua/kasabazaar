<?php

namespace App\Models;

use App\Enums\InvestmentStatus;
use App\Service\ExchangeRateService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investment extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'principal_amount' => 'decimal:2',
        'principal_amount_ghs' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'exchange_rate_at_investment' => 'decimal:4',
        'status' => InvestmentStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Investment $investment) {
            if (! $investment->reference) {
                $investment->reference = self::generateReference();
            }

            if (! $investment->exchange_rate_at_investment) {
                try {
                    $exchangeService = app(ExchangeRateService::class);
                    $investment->exchange_rate_at_investment = $exchangeService->getCurrentRate('USD', 'GHS');
                } catch (\Exception $e) {
                    $investment->exchange_rate_at_investment = 12.0; // Fallback
                }
            }

            if ($investment->principal_amount && $investment->exchange_rate_at_investment) {
                $investment->principal_amount_ghs = $investment->principal_amount * $investment->exchange_rate_at_investment;
            }

            if (! $investment->current_balance) {
                $investment->current_balance = $investment->principal_amount;
            }
        });
    }

    public static function generateReference(): string
    {
        $year = now()->year;

        do {
            $number = 'INV-'.$year.'-'.str_pad((string) mt_rand(0, 999_999), 6, '0', STR_PAD_LEFT);
        } while (self::where('reference', $number)->exists());

        return $number;
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    public function rateOverrides(): HasMany
    {
        return $this->hasMany(InvestmentRateOverride::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(InvestmentWithdrawalRequest::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

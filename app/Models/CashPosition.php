<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A bank and mobile-money balance as at a stated date, taken from the bank statement.
 * The balance sheet reads the most recent one at or before its as-of date.
 */
class CashPosition extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'as_of_date' => 'date',
        'bank_balance' => 'decimal:2',
        'momo_balance' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
    ];

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * The latest position recorded at or before the given date. A balance stands until
     * it is superseded, so a position recorded in June is what a September balance
     * sheet reports unless a later one exists.
     */
    public static function asOf(Carbon $date): ?self
    {
        return static::query()
            ->whereDate('as_of_date', '<=', $date)
            ->orderByDesc('as_of_date')
            ->first();
    }

    /** Bank balance converted into the presentation currency. */
    public function bankInPresentationCurrency(): float
    {
        return $this->convert((float) $this->bank_balance);
    }

    public function momoInPresentationCurrency(): float
    {
        return $this->convert((float) $this->momo_balance);
    }

    public function totalInPresentationCurrency(): float
    {
        return round($this->bankInPresentationCurrency() + $this->momoInPresentationCurrency(), 2);
    }

    private function convert(float $amount): float
    {
        if ($this->currency === config('financials.presentation_currency')) {
            return round($amount, 2);
        }

        $rate = (float) $this->exchange_rate;

        return $rate > 0 ? round($amount / $rate, 2) : round($amount, 2);
    }
}

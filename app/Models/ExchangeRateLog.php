<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRateLog extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'rate' => 'decimal:4',
        'rate_date' => 'date',
    ];

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get the latest rate for a currency pair
     */
    public static function getLatestRate(string $from = 'USD', string $to = 'GHS'): ?float
    {
        $log = self::where('from_currency', $from)
            ->where('to_currency', $to)
            ->orderBy('rate_date', 'desc')
            ->first();

        return $log?->rate;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get rate for a specific date
     */
    public static function getRateForDate(string $date, string $from = 'USD', string $to = 'GHS'): ?float
    {
        $log = self::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('rate_date', '<=', $date)
            ->orderBy('rate_date', 'desc')
            ->first();

        return $log?->rate;
    }
}

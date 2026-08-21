<?php

namespace App\Models;

use App\Enums\FiscalPeriodSource;
use App\Enums\FiscalPeriodStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalPeriod extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => FiscalPeriodStatus::class,
        'source' => FiscalPeriodSource::class,
        'closing_exchange_rate' => 'decimal:4',
        'entry_currency' => 'string',
        'locked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (FiscalPeriod $period) {
            // A fiscal year here is the calendar year — the cashbook is kept by
            // calendar month/year, so anything else would not tie out to it.
            $period->start_date ??= \Carbon\Carbon::create($period->year, 1, 1)->toDateString();
            $period->end_date ??= \Carbon\Carbon::create($period->year, 12, 31)->toDateString();
        });
    }

    public function balances()
    {
        return $this->hasMany(AccountBalance::class, 'fiscal_year', 'year');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isDerived(): bool
    {
        return $this->source === FiscalPeriodSource::derived;
    }

    /**
     * Whether this year's keyed balances need translating into the presentation
     * currency before they can appear on a statement.
     */
    public function needsTranslation(): bool
    {
        return $this->entry_currency !== config('financials.presentation_currency');
    }
}

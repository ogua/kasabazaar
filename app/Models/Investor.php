<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investor extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'default_annual_rate' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Investor $investor) {
            $investor->name = trim(collect([
                $investor->title,
                $investor->first_name,
                $investor->other_names,
            ])->filter()->implode(' '));
        });
    }

    /**
     * Masked name for contexts where the full identity must stay hidden
     * (e.g. investor lists shown during pitches) — title + first name only.
     */
    public function getDisplayNameAttribute(): string
    {
        return trim(collect([$this->title, $this->first_name])->filter()->implode(' ')) ?: $this->name;
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    /**
     * The default valuation date for combined (multi-tranche) agreement letters —
     * the latest of each investment's own defaultAsOfDate(). See Investment::defaultAsOfDate().
     */
    public function defaultAsOfDate(): Carbon
    {
        // Converted tranches are excluded: a settled tranche's posting cursor would
        // otherwise pull the valuation date forward past what the live tranches have
        // actually posted through, and every PDF anchored to this date would claim to
        // be as-of a period it has no figures for.
        $dates = $this->investments
            ->reject(fn (Investment $investment) => $investment->isConverted())
            ->map(fn (Investment $investment) => $investment->defaultAsOfDate());

        return $dates->max() ?? now();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(InvestmentWithdrawalRequest::class);
    }

    public function annualStatements(): HasMany
    {
        return $this->hasMany(InvestmentAnnualStatement::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(InvestmentConversion::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'investor_id');
    }
}

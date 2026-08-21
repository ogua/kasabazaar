<?php

namespace App\Models;

use App\Enums\InvestmentConversionDirection;
use App\Enums\InvestmentConversionStatus;
use App\Enums\InvestmentPayoutFrequency;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A novation of investor capital between the two instruments the company issues.
 * The source tranche(s) are settled and the same capital is re-issued as a new
 * tranche of the other capital_type — capital_type is never mutated on a row that
 * already has posted ledger history, which would invalidate its interest
 * postings, its agreement letter and its statement lines all at once.
 */
class InvestmentConversion extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'direction' => InvestmentConversionDirection::class,
        'status' => InvestmentConversionStatus::class,
        'conversion_date' => 'date',
        'total_principal_rolled' => 'decimal:2',
        'total_interest_rolled' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'requested_by_investor' => 'boolean',
        'maturity_exception_approved' => 'boolean',
        'threshold_exception_approved' => 'boolean',
        'target_contract_term_months' => 'integer',
        'target_payout_frequency' => InvestmentPayoutFrequency::class,
        'target_annual_rate' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InvestmentConversion $conversion) {
            if (! $conversion->reference) {
                $conversion->reference = self::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        $year = now()->year;

        do {
            $number = 'CNV-'.$year.'-'.str_pad((string) mt_rand(0, 999_999), 6, '0', STR_PAD_LEFT);
        } while (self::withTrashed()->where('reference', $number)->exists());

        return $number;
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(InvestmentConversionSource::class);
    }

    public function targetInvestment(): BelongsTo
    {
        return $this->belongsTo(Investment::class, 'target_investment_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}

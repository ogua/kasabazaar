<?php

namespace App\Models;

use App\Enums\InvestmentAgreementStatus;
use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentPayoutFrequency;
use App\Enums\InvestmentStatus;
use App\Service\ExchangeRateService;
use Carbon\Carbon;
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
        'maturity_date' => 'date',
        'contract_term_months' => 'integer',
        'principal_amount' => 'decimal:2',
        'principal_amount_ghs' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'exchange_rate_at_investment' => 'decimal:4',
        'status' => InvestmentStatus::class,
        'capital_type' => InvestmentCapitalType::class,
        'agreement_status' => InvestmentAgreementStatus::class,
        'agreement_signed_at' => 'datetime',
        'agreement_finalized_at' => 'datetime',
        'last_interest_posted_through' => 'date',
        'payout_frequency' => InvestmentPayoutFrequency::class,
        'next_payout_due_date' => 'date',
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

        static::saving(function (Investment $investment) {
            // On create, every attribute is "dirty" (including start_date), so an
            // explicitly-provided maturity_date — e.g. matching an already-signed
            // physical agreement — must be distinguished from "not provided yet" by
            // checking $investment->exists rather than isDirty() alone, or it would
            // be silently overwritten by the auto-calculated value below.
            $maturityExplicitlyProvided = ! $investment->exists && $investment->maturity_date !== null;

            if ($investment->start_date && ! $maturityExplicitlyProvided && (
                $investment->isDirty(['start_date', 'contract_term_months']) || ! $investment->maturity_date
            )) {
                $investment->maturity_date = Carbon::parse($investment->start_date)
                    ->addMonths($investment->contract_term_months ?? 12);
            }

            // Only (re)seed the payout cursor for a loan that hasn't started its
            // schedule yet — once a payout has been generated, editing the record
            // must not silently reset how far through the schedule it already is.
            if ($investment->capital_type === InvestmentCapitalType::loan
                && $investment->start_date
                && $investment->payout_frequency
                && ($investment->isDirty(['start_date', 'payout_frequency']) || ! $investment->next_payout_due_date)
                && ($investment->exists === false || ! $investment->interestPayouts()->exists())
            ) {
                $investment->next_payout_due_date = Carbon::parse($investment->start_date)
                    ->addMonths($investment->payout_frequency->months());
            }
        });
    }

    /**
     * Whether the investment's contract term has elapsed. Withdrawal requests
     * (partial or full) may only be submitted once the contract is due — the
     * investor commits to holding the principal for the agreed term before
     * returns or principal can be requested back.
     */
    public function isContractDue(): bool
    {
        return $this->maturity_date !== null && now()->gte($this->maturity_date);
    }

    /**
     * The default valuation date used for agreement letters/statements when no
     * explicit as-of date is requested. Always a real, already-closed accounting
     * date (never "today") so the same request produces the same figure regardless
     * of when it's generated: the end of the most recently posted interest period,
     * or the investment's start date if no interest has been posted yet.
     */
    public function defaultAsOfDate(): Carbon
    {
        if ($this->last_interest_posted_through) {
            return Carbon::parse($this->last_interest_posted_through);
        }

        return Carbon::parse($this->start_date);
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

    public function interestPayouts(): HasMany
    {
        return $this->hasMany(InvestmentInterestPayout::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function agreementFinalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agreement_finalized_by');
    }
}

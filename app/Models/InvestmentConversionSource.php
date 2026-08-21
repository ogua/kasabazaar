<?php

namespace App\Models;

use App\Enums\InvestmentConversionSourceMode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One source tranche contributing to a conversion, with the settlement figures
 * snapshotted at execution time so a document rendered later reproduces exactly
 * what the parties agreed rather than what today's rates would compute.
 */
class InvestmentConversionSource extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'mode' => InvestmentConversionSourceMode::class,
        'principal_at_conversion' => 'decimal:2',
        'interest_at_conversion' => 'decimal:2',
        'amount_rolled' => 'decimal:2',
        'amount_paid_out' => 'decimal:2',
        'remaining_balance_after' => 'decimal:2',
        'source_fully_closed' => 'boolean',
    ];

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(InvestmentConversion::class, 'investment_conversion_id');
    }

    public function sourceInvestment(): BelongsTo
    {
        return $this->belongsTo(Investment::class, 'source_investment_id');
    }
}

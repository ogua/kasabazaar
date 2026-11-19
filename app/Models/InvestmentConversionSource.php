<?php

namespace App\Models;

use App\Enums\InvestmentConversionSourceMode;
use App\Models\Concerns\Auditable;
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
    use Auditable, HasUuids, SoftDeletes;

    protected static string $auditLog = 'investors';

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

    /**
     * @return array{0: class-string, 1: mixed}|null
     */
    public function auditScope(): ?array
    {
        return [InvestmentConversion::class, $this->investment_conversion_id];
    }
}

<?php

namespace App\Models;

use App\Enums\InvestmentInterestPayoutStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentInterestPayout extends Model
{
    use Auditable, HasUuids, SoftDeletes;

    protected static string $auditLog = 'investors';

    /** @var list<string> */
    protected static array $auditHighRisk = ['amount', 'status'];

    protected $guarded = ['id'];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'reviewed_at' => 'datetime',
        'status' => InvestmentInterestPayoutStatus::class,
        'principal_balance' => 'decimal:2',
        'rate_applied' => 'decimal:2',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return array{0: class-string, 1: mixed}|null
     */
    public function auditScope(): ?array
    {
        return [Investment::class, $this->investment_id];
    }
}

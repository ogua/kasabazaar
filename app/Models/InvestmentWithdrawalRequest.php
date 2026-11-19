<?php

namespace App\Models;

use App\Enums\InvestmentWithdrawalStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentWithdrawalRequest extends Model
{
    use Auditable, HasUuids, SoftDeletes;

    protected static string $auditLog = 'investors';

    /** @var list<string> */
    protected static array $auditHighRisk = ['requested_amount', 'amount_paid', 'status', 'exception_approved', 'payment_reference'];

    protected $guarded = ['id'];

    protected $casts = [
        'notice_date' => 'date',
        'required_action_by' => 'date',
        'payment_due_by' => 'date',
        'paid_at' => 'date',
        'reviewed_at' => 'datetime',
        'status' => InvestmentWithdrawalStatus::class,
        'is_full_withdrawal' => 'boolean',
        'exception_approved' => 'boolean',
        'requested_amount' => 'decimal:2',
        'remaining_balance_after' => 'decimal:2',
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

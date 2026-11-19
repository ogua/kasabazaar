<?php

namespace App\Models;

use App\Enums\InvestmentTransactionType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentTransaction extends Model
{
    use Auditable, HasUuids, SoftDeletes;

    protected static string $auditLog = 'investors';

    /** @var list<string> */
    protected static array $auditExcept = ['op_balance', 'cl_balance'];

    /** @var list<string> */
    protected static array $auditHighRisk = ['debit', 'credit', 'rate_applied', 'posted'];

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'type' => InvestmentTransactionType::class,
        'op_balance' => 'decimal:2',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'cl_balance' => 'decimal:2',
        'rate_applied' => 'decimal:2',
        'exchange_rate_at_transaction' => 'decimal:4',
        'amount_ghs' => 'decimal:2',
        'posted' => 'boolean',
        'posted_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (InvestmentTransaction $transaction) {
            // CL = OP - DR + CR, same convention as CashbookLoan
            $transaction->cl_balance = round(
                (float) $transaction->op_balance - (float) $transaction->debit + (float) $transaction->credit,
                2
            );
        });
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * @return array{0: class-string, 1: mixed}|null
     */
    public function auditScope(): ?array
    {
        return [Investment::class, $this->investment_id];
    }
}

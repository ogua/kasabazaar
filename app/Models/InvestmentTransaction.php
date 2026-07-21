<?php

namespace App\Models;

use App\Enums\InvestmentTransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentTransaction extends Model
{
    use HasUuids, SoftDeletes;

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
}

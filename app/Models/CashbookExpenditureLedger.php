<?php

namespace App\Models;

use App\Enums\ExpenditureLedgerType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CashbookExpenditureLedger extends Model
{
    use HasUuids;

    protected $table = 'cashbook_expenditure_ledger';

    protected $guarded = ['id'];

    protected $casts = [
        'ledger_type' => ExpenditureLedgerType::class,
        'op_balance'  => 'decimal:2',
        'debit'       => 'decimal:2',
        'credit'      => 'decimal:2',
        'cl_balance'  => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (CashbookExpenditureLedger $ledger) {
            // CL = OP + DR - CR  (expenses grow with debits)
            $ledger->cl_balance = round(
                (float) $ledger->op_balance + (float) $ledger->debit - (float) $ledger->credit,
                2
            );
        });
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }
}

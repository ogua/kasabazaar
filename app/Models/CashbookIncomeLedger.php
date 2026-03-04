<?php

namespace App\Models;

use App\Enums\IncomeLedgerType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CashbookIncomeLedger extends Model
{
    use HasUuids;

    protected $table = 'cashbook_income_ledger';

    protected $guarded = ['id'];

    protected $casts = [
        'ledger_type' => IncomeLedgerType::class,
        'op_balance'  => 'decimal:2',
        'debit'       => 'decimal:2',
        'credit'      => 'decimal:2',
        'cl_balance'  => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (CashbookIncomeLedger $ledger) {
            // CL = OP + CR - DR  (income grows with credits)
            $ledger->cl_balance = round(
                (float) $ledger->op_balance + (float) $ledger->credit - (float) $ledger->debit,
                2
            );
        });
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }
}

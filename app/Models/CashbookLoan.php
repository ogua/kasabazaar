<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashbookLoan extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'date'       => 'date',
        'op_balance' => 'decimal:2',
        'debit'      => 'decimal:2',
        'credit'     => 'decimal:2',
        'cl_balance' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (CashbookLoan $loan) {
            // CL = OP - DR  (payments reduce outstanding loan balance)
            $loan->cl_balance = round(
                (float) $loan->op_balance - (float) $loan->debit + (float) $loan->credit,
                2
            );
        });
    }

    public function scopeForLender($query, string $lenderName)
    {
        return $query->where('lender_name', $lenderName);
    }
}

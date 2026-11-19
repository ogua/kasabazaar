<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashbookDirectorAccount extends Model
{
    use Auditable, HasUuids, SoftDeletes;

    protected static string $auditLog = 'cashbook';

    protected $table = 'cashbook_director_account';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'op_balance' => 'decimal:2',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'cl_balance' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (CashbookDirectorAccount $record) {
            // CL = OP + DR (director injects funds; debit increases balance)
            $record->cl_balance = round(
                (float) $record->op_balance + (float) $record->debit - (float) $record->credit,
                2
            );
        });
    }
}

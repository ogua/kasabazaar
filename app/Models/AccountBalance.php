<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalance extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'finance';

    protected $guarded = ['id'];

    protected $casts = [
        'fiscal_year' => 'integer',
        'opening_balance' => 'decimal:2',
        'movement' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (AccountBalance $balance) {
            // Closing always follows from opening + movement, the same auto-computed
            // convention CashbookEntry and InvestmentTransaction use for their balances.
            $balance->closing_balance = round(
                (float) $balance->opening_balance + (float) $balance->movement,
                2
            );
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}

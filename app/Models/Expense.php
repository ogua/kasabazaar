<?php

namespace App\Models;

use App\Enums\ExpenseStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'expense_date' => 'date',
        'amount_usd' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_ghs' => 'decimal:2',
        'expense_stage' => ExpenseStage::class,
    ];

    protected static function booted()
    {
        static::creating(function ($expense) {
            if (empty($expense->reference)) {
                $expense->reference = self::generateReference();
            }
            if ($expense->amount_usd && $expense->exchange_rate) {
                $expense->amount_ghs = $expense->amount_usd * $expense->exchange_rate;
            }
        });

        static::updating(function ($expense) {
            if ($expense->amount_usd && $expense->exchange_rate) {
                $expense->amount_ghs = $expense->amount_usd * $expense->exchange_rate;
            }
        });
    }

    public static function generateReference(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now())->count() + 1;
        return sprintf('EXP-%s-%04d', $date, $count);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

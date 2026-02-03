<?php

namespace App\Models;

use App\Enums\PayrollEntryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEntry extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'overtime' => 'decimal:2',
        'bonus' => 'decimal:2',
        'allowances' => 'decimal:2',
        'tax' => 'decimal:2',
        'ssnit' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'paid_at' => 'date',
        'status' => PayrollEntryStatus::class,
    ];

    protected static function booted()
    {
        static::saving(function ($entry) {
            // Calculate gross pay
            $entry->gross_pay = $entry->base_salary + $entry->overtime + $entry->bonus + $entry->allowances;

            // Calculate total deductions
            $entry->total_deductions = $entry->tax + $entry->ssnit + $entry->other_deductions;

            // Calculate net pay
            $entry->net_salary = $entry->gross_pay - $entry->total_deductions;
        });
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}

<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPeriod extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pay_date' => 'date',
        'approved_at' => 'datetime',
        'status' => PayrollStatus::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function getTotalGrossPayAttribute(): float
    {
        return $this->entries()->sum('gross_pay');
    }

    public function getTotalNetPayAttribute(): float
    {
        return $this->entries()->sum('net_salary');
    }

    public function getTotalDeductionsAttribute(): float
    {
        return $this->entries()->sum('total_deductions');
    }
}

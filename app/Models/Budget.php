<?php

namespace App\Models;

use App\Enums\PeriodType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'period_type' => PeriodType::class,
        'budgeted_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($budget) {
            $budget->variance = $budget->budgeted_amount - $budget->actual_amount;
            if ($budget->budgeted_amount > 0) {
                $budget->variance_percentage = ($budget->variance / $budget->budgeted_amount) * 100;
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isOverBudget(): bool
    {
        return $this->actual_amount > $this->budgeted_amount;
    }

    public function isUnderBudget(): bool
    {
        return $this->actual_amount < $this->budgeted_amount;
    }

    public function getRemainingBudgetAttribute(): float
    {
        return max(0, $this->budgeted_amount - $this->actual_amount);
    }

    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->budgeted_amount <= 0) {
            return 0;
        }
        return ($this->actual_amount / $this->budgeted_amount) * 100;
    }

    public function getStatusAttribute(): string
    {
        $utilization = $this->utilization_percentage;
        if ($utilization >= 100) {
            return 'over_budget';
        } elseif ($utilization >= 90) {
            return 'critical';
        } elseif ($utilization >= 75) {
            return 'warning';
        }
        return 'healthy';
    }
}

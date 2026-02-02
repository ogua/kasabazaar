<?php

namespace App\Models;

use App\Enums\PeriodType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTarget extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'period_type' => PeriodType::class,
        'target_value' => 'decimal:2',
        'achieved_value' => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($kpi) {
            if ($kpi->target_value > 0) {
                $kpi->achievement_percentage = ($kpi->achieved_value / $kpi->target_value) * 100;
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isOnTrack(): bool
    {
        return $this->achievement_percentage >= 100;
    }

    public function getStatusAttribute(): string
    {
        if ($this->achievement_percentage >= 100) {
            return 'achieved';
        } elseif ($this->achievement_percentage >= 75) {
            return 'on_track';
        } elseif ($this->achievement_percentage >= 50) {
            return 'at_risk';
        }
        return 'behind';
    }
}

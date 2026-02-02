<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'max_weight_kg' => 'decimal:2',
        'max_volume_cbm' => 'decimal:2',
        'status' => VehicleStatus::class,
        'insurance_expiry' => 'date',
        'roadworthy_expiry' => 'date',
        'registration_expiry' => 'date',
        'last_service_date' => 'date',
        'next_service_due' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', VehicleStatus::Available);
    }

    public function isInsuranceExpiringSoon(int $days = 30): bool
    {
        if (!$this->insurance_expiry) {
            return false;
        }
        return $this->insurance_expiry->diffInDays(now()) <= $days && $this->insurance_expiry->isFuture();
    }

    public function isRoadworthyExpiringSoon(int $days = 30): bool
    {
        if (!$this->roadworthy_expiry) {
            return false;
        }
        return $this->roadworthy_expiry->diffInDays(now()) <= $days && $this->roadworthy_expiry->isFuture();
    }

    public function isServiceDue(): bool
    {
        if (!$this->next_service_due) {
            return false;
        }
        return $this->next_service_due->isPast() || $this->next_service_due->isToday();
    }
}

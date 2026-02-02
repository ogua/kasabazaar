<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'salary' => 'decimal:2',
        'hire_date' => 'date',
        'employment_status' => EmploymentStatus::class,
    ];

    protected static function booted()
    {
        static::creating(function ($staff) {
            if (empty($staff->employee_id)) {
                $staff->employee_id = self::generateEmployeeId();
            }
        });
    }

    public static function generateEmployeeId(): string
    {
        $count = self::withTrashed()->count() + 1;
        return sprintf('EMP-%04d', $count);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(StaffRole::class, 'staff_role_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrollEntries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(ClientInteraction::class);
    }

    public function tripsAsDriver(): HasMany
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    public function tripsAsAssistant(): HasMany
    {
        return $this->hasMany(Trip::class, 'assistant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('employment_status', EmploymentStatus::Active);
    }

    public function scopeDrivers($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('code', 'DRIVER');
        });
    }

    public function isDriver(): bool
    {
        return $this->role?->code === 'DRIVER';
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Shipment;
use App\Models\Staff;
use App\Models\ScheduleItem;
use App\Models\User;

class PickupSchedule extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public const STATUSES = [
        'scheduled'   => 'Scheduled',
        'confirmed'   => 'Confirmed',
        'in-progress' => 'In Progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
        'converted'   => 'Converted',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    public function pickupItems(): HasMany
    {
        return $this->hasMany(ScheduleItem::class, 'pickup_schedule_id');
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'scheduled'   => 'info',
            'confirmed'   => 'primary',
            'in-progress' => 'warning',
            'completed'   => 'success',
            'converted'   => 'success',
            'cancelled'   => 'danger',
            default       => 'gray',
        };
    }
}

<?php

namespace App\Models;

use App\Enums\TripStatus;
use App\Models\Branch;
use App\Models\Shipment;
use App\Models\Staff;
use App\Models\TripShipment;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Trip extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'distance_km'         => 'decimal:2',
        'scheduled_date'      => 'datetime',
        'scheduled_departure' => 'datetime',
        'scheduled_arrival'   => 'datetime',
        'actual_departure'    => 'datetime',
        'actual_arrival'      => 'datetime',
        'status'              => TripStatus::class,
        'fuel_cost' => 'decimal:2',
        'toll_fees' => 'decimal:2',
        'driver_allowance' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($trip) {
            if (empty($trip->trip_reference)) {
                $trip->trip_reference = self::generateReference();
            }
        });

        static::saving(function ($trip) {
            $trip->total_cost = $trip->fuel_cost + $trip->toll_fees + $trip->driver_allowance + $trip->other_costs;
        });
    }

    public static function generateReference(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now())->count() + 1;
        return sprintf('TRIP-%s-%03d', $date, $count);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'driver_id');
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assistant_id');
    }

    public function tripShipments(): HasMany
    {
        return $this->hasMany(TripShipment::class);
    }

    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(Shipment::class, 'trip_shipments')
            ->withPivot(['delivery_status', 'delivery_notes', 'delivered_at', 'receiver_signature'])
            ->withTimestamps();
    }

    public function getDeliveredCountAttribute(): int
    {
        return $this->tripShipments()->where('delivery_status', 'delivered')->count();
    }

    public function getTotalShipmentsAttribute(): int
    {
        return $this->tripShipments()->count();
    }

    public function getDeliveryRateAttribute(): float
    {
        if ($this->total_shipments === 0) {
            return 0;
        }
        return ($this->delivered_count / $this->total_shipments) * 100;
    }
}

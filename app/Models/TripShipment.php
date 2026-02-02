<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripShipment extends Model
{
    use HasUuids;

   // protected $guarded = ['id'];

    protected $casts = [
        'delivery_status' => DeliveryStatus::class,
        'delivered_at' => 'datetime',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}

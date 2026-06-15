<?php

namespace App\Models;

use App\Enums\EcommerceOrderShipmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcommerceOrderShipment extends Model
{
    use HasUuids;

    protected $table = 'ecommerce_order_shipments';

    protected function casts(): array
    {
        return [
            'status' => EcommerceOrderShipmentStatus::class,
            'estimated_delivery' => 'date',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'delivery_person_id');
    }

    public function trackingLogs(): HasMany
    {
        return $this->hasMany(EcommerceShipmentTrackingLog::class, 'order_shipment_id')->orderBy('recorded_at');
    }
}

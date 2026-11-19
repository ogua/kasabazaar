<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'shipments';

    protected function casts(): array
    {
        return [
            'is_vehicle' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Receiver::class, 'receiver_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function scopeVehicles(Builder $query): Builder
    {
        return $query->where('is_vehicle', true);
    }

    /**
     * @return array{0: class-string, 1: mixed}|null
     */
    public function auditScope(): ?array
    {
        return [Shipment::class, $this->shipment_id];
    }
}

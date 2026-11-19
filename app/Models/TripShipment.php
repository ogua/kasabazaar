<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripShipment extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'fleet';

    /** @var list<string> */
    protected static array $auditRedacted = ['receiver_signature'];

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

    /**
     * @return array{0: class-string, 1: mixed}|null
     */
    public function auditScope(): ?array
    {
        return [Trip::class, $this->trip_id];
    }
}

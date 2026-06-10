<?php

namespace App\Models;

use App\Enums\ShipmentRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentRequest extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'status'              => ShipmentRequestStatus::class,
        'receivers'           => 'array',
        'preferred_pickup_at' => 'datetime',
        'reviewed_at'         => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

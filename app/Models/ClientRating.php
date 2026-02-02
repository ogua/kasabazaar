<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientRating extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'total_lifetime_value' => 'decimal:2',
        'is_vip' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Update statistics based on client's shipment history
     */
    public function updateStatistics(): void
    {
        $client = $this->client;
        $shipments = $client->shipments;

        $this->total_shipments = $shipments->count();
        $this->total_lifetime_value = $shipments->sum('total');

        // Determine shipment frequency
        $lastYearShipments = $shipments->where('created_at', '>=', now()->subYear())->count();
        if ($lastYearShipments >= 12) {
            $this->shipment_frequency = 'high';
        } elseif ($lastYearShipments >= 4) {
            $this->shipment_frequency = 'medium';
        } elseif ($lastYearShipments >= 1) {
            $this->shipment_frequency = 'low';
        } else {
            $this->shipment_frequency = 'inactive';
        }

        $this->save();
    }
}

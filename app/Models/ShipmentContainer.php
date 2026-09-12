<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentContainer extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_cleared' => 'boolean',
        'cleared_at' => 'datetime',
    ];

    /**
     * Get all shipments in this container.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'container_number', 'container_number');
    }

    /**
     * The agent currently on record as having cleared (or unset) this container.
     */
    public function clearingAgent(): BelongsTo
    {
        return $this->belongsTo(ClearingAgent::class);
    }

    /**
     * Full clearance history for this container, oldest first.
     */
    public function clearances(): HasMany
    {
        return $this->hasMany(ContainerClearance::class, 'container_number', 'container_number');
    }

    /**
     * Human-readable container reference e.g. "CON49-25".
     */
    public function getContainerRefAttribute(): string
    {
        return 'CON'.$this->container_number
            .($this->container_year ? '-'.$this->container_year : '');
    }

    /**
     * Scope: only cleared containers.
     */
    public function scopeCleared($query)
    {
        return $query->where('is_cleared', true);
    }

    /**
     * Scope: only pending (not cleared) containers.
     */
    public function scopePending($query)
    {
        return $query->where('is_cleared', false);
    }
}

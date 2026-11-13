<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PickupStation extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'is_pickup_point' => 'boolean',
            'is_warehouse' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Where customers can collect orders.
     */
    public function scopePickupPoints(Builder $query): Builder
    {
        return $query->active()->where('is_pickup_point', true);
    }

    /**
     * Where vendors deliver items for inspection, and where returns arrive.
     */
    public function scopeWarehouses(Builder $query): Builder
    {
        return $query->active()->where('is_warehouse', true);
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->city, $this->region, $this->country])->filter()->implode(', ');
    }
}

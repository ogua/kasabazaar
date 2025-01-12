<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasUuids;

    public function getFullnameBranchAttribute()
    {
        return "{$this->name} ({$this->branch?->name})";
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mcity(): BelongsTo
    {
        return $this->belongsTo(City::class,"city");
    }

    public function mstate(): BelongsTo
    {
        return $this->belongsTo(State::class,"state_region");
    }


    public function mcountry(): BelongsTo
    {
        return $this->belongsTo(Country::class,"country");
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receiver extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

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

    public function items()
    {
        return $this->hasMany(ShipmentItem::class,"receiver_id");
    }
}

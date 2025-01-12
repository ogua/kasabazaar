<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasUuids;

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class,"shipment_id");
    }
}

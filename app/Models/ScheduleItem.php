<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleItem extends Model
{
    use HasUuids;

     public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class,"product_id");
    }
}

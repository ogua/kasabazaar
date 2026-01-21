<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class,"shipment_id");
    }


    public function enteredby(): BelongsTo
    {
        return $this->belongsTo(User::class,"user_id");
    }

    public function scopeOfbranch($query)
    {
        $query->where('branch_id',Filament::getTenant()->id);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    use HasUuids;

    public function items()
    {
        return $this->hasMany(QuotationItem::class,"quotation_id");
    }

    public function branch() : BelongsTo {
        return $this->belongsTo(Branch::class);
    }

    public function enteredby(): BelongsTo
    {
        return $this->belongsTo(User::class,"recorderd_by");
    }

    public function client() : BelongsTo {
        return $this->belongsTo(Client::class,"client_id");
    }
}

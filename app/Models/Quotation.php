<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'shipments';

    public function items()
    {
        return $this->hasMany(QuotationItem::class, 'quotation_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorderd_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}

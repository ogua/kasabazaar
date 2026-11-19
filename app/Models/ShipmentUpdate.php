<?php

namespace App\Models;

use App\Services\Audit\AuditContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentUpdate extends Model
{
    use HasUlids;

    const CREATED_AT = null;

    protected static function booted(): void
    {
        // Who moved the shipment to this stage — the signed-in user, or the user a queued job
        // (bulk container status) was dispatched by. Internal only: public tracking never reads it.
        static::creating(function (ShipmentUpdate $update): void {
            $update->recorded_by ??= AuditContext::instance()->causerId();
        });
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

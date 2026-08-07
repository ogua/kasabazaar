<?php

namespace App\Models;

use App\Enums\VendorPayoutStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayoutRequest extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'amount_ghs' => 'decimal:2',
            'status' => VendorPayoutStatus::class,
            'payout_details' => 'encrypted',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}

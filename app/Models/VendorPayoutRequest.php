<?php

namespace App\Models;

use App\Enums\VendorPayoutStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayoutRequest extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'marketplace';

    /** @var list<string> */
    protected static array $auditRedacted = ['payout_details'];

    /** @var list<string> */
    protected static array $auditHighRisk = ['amount_ghs', 'status'];

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

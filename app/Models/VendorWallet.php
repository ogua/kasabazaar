<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorWallet extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'balance_ghs' => 'decimal:2',
            'pending_balance_ghs' => 'decimal:2',
            'lifetime_earnings_ghs' => 'decimal:2',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}

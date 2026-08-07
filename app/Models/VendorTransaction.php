<?php

namespace App\Models;

use App\Enums\VendorTransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorTransaction extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'type' => VendorTransactionType::class,
            'amount_ghs' => 'decimal:2',
            'balance_after_ghs' => 'decimal:2',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'ecommerce_order_id');
    }
}

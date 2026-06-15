<?php

namespace App\Models;

use App\Enums\EcommerceInventoryLogType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceInventoryLog extends Model
{
    use HasUuids;

    protected $table = 'ecommerce_inventory_logs';

    protected function casts(): array
    {
        return [
            'type' => EcommerceInventoryLogType::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(EcommerceProduct::class, 'ecommerce_product_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

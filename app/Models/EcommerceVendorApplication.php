<?php

namespace App\Models;

use App\Enums\EcommerceVendorApplicationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceVendorApplication extends Model
{
    use HasUuids;

    protected $table = 'ecommerce_vendor_applications';

    protected function casts(): array
    {
        return [
            'status' => EcommerceVendorApplicationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

<?php

namespace App\Models;

use App\Enums\EcommerceVendorApplicationStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceVendorApplication extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'marketplace';

    /** @var list<string> */
    protected static array $auditRedacted = ['ghana_card_front_path', 'ghana_card_back_path'];

    /** @var list<string> */
    protected static array $auditHighRisk = ['status'];

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

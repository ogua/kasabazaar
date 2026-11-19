<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceReturn extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'marketplace';

    /** @var list<string> */
    protected static array $auditHighRisk = ['status', 'refunded_at'];

    public const REQUESTED = 'requested';

    public const APPROVED = 'approved';

    public const RECEIVED = 'received';

    public const INSPECTED = 'inspected';

    public const RETURNED_TO_VENDOR = 'returned_to_vendor';

    public const REFUNDED = 'refunded';

    public const REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'received_at' => 'datetime',
            'inspected_at' => 'datetime',
            'returned_to_vendor_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $return) {
            $return->return_number ??= 'RTN-'.now()->format('ymd').'-'.strtoupper(substr(str_replace('-', '', (string) \Illuminate\Support\Str::uuid()), 0, 6));
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(PickupStation::class, 'warehouse_id');
    }

    /**
     * @return array{0: class-string, 1: mixed}|null
     */
    public function auditScope(): ?array
    {
        return [EcommerceOrder::class, $this->order_id];
    }
}

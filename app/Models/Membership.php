<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'marketplace';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    protected function casts(): array
    {
        return [
            'amount_ghs' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    /**
     * Paid and not yet past its expiry date.
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)->where('expires_at', '>', now());
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->expires_at?->isFuture();
    }
}

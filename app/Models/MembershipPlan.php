<?php

namespace App\Models;

use App\Enums\MembershipTier;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'tier' => MembershipTier::class,
            'price_ghs' => 'decimal:2',
            'product_discount_percent' => 'decimal:2',
            'exclusive_auctions' => 'boolean',
            'perks' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}

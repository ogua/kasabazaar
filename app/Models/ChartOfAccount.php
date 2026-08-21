<?php

namespace App\Models;

use App\Enums\AccountSubtype;
use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasUuids;

    protected $table = 'chart_of_accounts';

    protected $guarded = ['id'];

    protected $casts = [
        'type' => AccountType::class,
        'subtype' => AccountSubtype::class,
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(AccountBalance::class);
    }

    public function balanceForYear(int $year): ?AccountBalance
    {
        return $this->balances()->where('fiscal_year', $year)->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, AccountType $type): Builder
    {
        return $query->where('type', $type->value);
    }
}

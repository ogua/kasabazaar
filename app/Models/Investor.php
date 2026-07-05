<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investor extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(InvestmentWithdrawalRequest::class);
    }

    public function annualStatements(): HasMany
    {
        return $this->hasMany(InvestmentAnnualStatement::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'investor_id');
    }
}

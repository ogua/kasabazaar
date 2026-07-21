<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investor extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'default_annual_rate' => 'decimal:2',
    ];

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    /**
     * The default valuation date for combined (multi-tranche) agreement letters —
     * the latest of each investment's own defaultAsOfDate(). See Investment::defaultAsOfDate().
     */
    public function defaultAsOfDate(): Carbon
    {
        $dates = $this->investments->map(fn (Investment $investment) => $investment->defaultAsOfDate());

        return $dates->max() ?? now();
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

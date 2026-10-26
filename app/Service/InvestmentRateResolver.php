<?php

namespace App\Service;

use App\Exceptions\MissingInvestmentRateException;
use App\Models\Investment;
use App\Models\InvestmentRateOverride;
use App\Models\InvestmentRateSetting;

class InvestmentRateResolver
{
    /**
     * Resolution order, most specific first:
     *   1. A one-off override for this exact investment tranche and year.
     *   2. The investor's own standing rate, which applies every year until changed.
     *   3. The company-wide default rate for the year.
     *   4. The standard rate in config('investment.default_annual_rate') (15%).
     *
     * @return array{rate: float, source: string}
     */
    public function resolve(Investment $investment, int $year): array
    {
        $override = InvestmentRateOverride::where('investment_id', $investment->id)
            ->where('year', $year)
            ->first();

        if ($override) {
            return ['rate' => (float) $override->annual_rate, 'source' => 'override'];
        }

        $investorRate = $investment->investor?->default_annual_rate;

        if ($investorRate !== null) {
            return ['rate' => (float) $investorRate, 'source' => 'investor_default'];
        }

        $setting = InvestmentRateSetting::forYear($year);

        if ($setting) {
            return ['rate' => (float) $setting->annual_rate, 'source' => 'company_default'];
        }

        $standardRate = config('investment.default_annual_rate');

        if (filled($standardRate)) {
            return ['rate' => (float) $standardRate, 'source' => 'standard_rate'];
        }

        throw new MissingInvestmentRateException($year);
    }
}

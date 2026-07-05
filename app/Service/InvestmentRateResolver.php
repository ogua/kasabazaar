<?php

namespace App\Service;

use App\Exceptions\MissingInvestmentRateException;
use App\Models\Investment;
use App\Models\InvestmentRateOverride;
use App\Models\InvestmentRateSetting;

class InvestmentRateResolver
{
    /**
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

        $setting = InvestmentRateSetting::forYear($year);

        if ($setting) {
            return ['rate' => (float) $setting->annual_rate, 'source' => 'company_default'];
        }

        throw new MissingInvestmentRateException($year);
    }
}

<?php

namespace App\Services;

use App\Models\TaxRule;

class TaxService
{
    public function calculate(float $amountGhs): float
    {
        $rule = TaxRule::where('is_active', true)->first();

        if (! $rule) {
            return 0;
        }

        return round($amountGhs * ((float) $rule->rate_percent / 100), 2);
    }
}

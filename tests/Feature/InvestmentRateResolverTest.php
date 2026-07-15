<?php

namespace Tests\Feature;

use App\Exceptions\MissingInvestmentRateException;
use App\Models\Investment;
use App\Models\InvestmentRateOverride;
use App\Models\InvestmentRateSetting;
use App\Models\Investor;
use App\Service\InvestmentRateResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestmentRateResolverTest extends TestCase
{
    use DatabaseTransactions;

    public function test_falls_back_to_company_default_when_investor_has_no_custom_rate(): void
    {
        InvestmentRateSetting::firstOrCreate(['year' => 2025], ['annual_rate' => 12]);

        $investor = Investor::create(['name' => 'Company Default Investor', 'status' => 'active']);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2025-01-01',
        ]);

        $result = app(InvestmentRateResolver::class)->resolve($investment, 2025);

        $this->assertEquals(12.0, $result['rate']);
        $this->assertSame('company_default', $result['source']);
    }

    public function test_uses_investor_default_rate_when_set_regardless_of_company_default(): void
    {
        InvestmentRateSetting::firstOrCreate(['year' => 2025], ['annual_rate' => 12]);

        $investor = Investor::create([
            'name' => 'Custom Rate Investor',
            'status' => 'active',
            'default_annual_rate' => 20,
        ]);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2025-01-01',
        ]);

        $result = app(InvestmentRateResolver::class)->resolve($investment, 2025);

        $this->assertEquals(20.0, $result['rate']);
        $this->assertSame('investor_default', $result['source']);
    }

    public function test_investor_default_rate_applies_across_multiple_years_without_new_overrides(): void
    {
        $investor = Investor::create([
            'name' => 'Multi Year Rate Investor',
            'status' => 'active',
            'default_annual_rate' => 18,
        ]);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2024-01-01',
        ]);

        $resolver = app(InvestmentRateResolver::class);

        foreach ([2024, 2025, 2026] as $year) {
            $result = $resolver->resolve($investment, $year);
            $this->assertEquals(18.0, $result['rate']);
            $this->assertSame('investor_default', $result['source']);
        }
    }

    public function test_investment_specific_override_takes_priority_over_investor_default(): void
    {
        $investor = Investor::create([
            'name' => 'Override Priority Investor',
            'status' => 'active',
            'default_annual_rate' => 15,
        ]);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2025-01-01',
        ]);

        InvestmentRateOverride::create([
            'investment_id' => $investment->id,
            'year' => 2025,
            'annual_rate' => 25,
        ]);

        $result = app(InvestmentRateResolver::class)->resolve($investment, 2025);

        $this->assertEquals(25.0, $result['rate']);
        $this->assertSame('override', $result['source']);

        // A year without an override still falls back to the investor's own default.
        $resultNextYear = app(InvestmentRateResolver::class)->resolve($investment, 2026);
        $this->assertEquals(15.0, $resultNextYear['rate']);
        $this->assertSame('investor_default', $resultNextYear['source']);
    }

    public function test_throws_when_no_rate_is_configured_anywhere(): void
    {
        $investor = Investor::create(['name' => 'No Rate Investor', 'status' => 'active']);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2019-01-01',
        ]);

        $this->expectException(MissingInvestmentRateException::class);

        app(InvestmentRateResolver::class)->resolve($investment, 2019);
    }
}

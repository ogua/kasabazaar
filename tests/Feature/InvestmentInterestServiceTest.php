<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\InvestmentRateSetting;
use App\Models\Investor;
use App\Models\User;
use App\Service\InvestmentInterestService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestmentInterestServiceTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Regression fixture from the real signed investment agreement (Madam Susie Prah,
     * two tranches: $20,000 in June 2024 and $20,000 in October 2024, 17% for 2024,
     * 15% for 2025, annual compounding, 365-day proration).
     */
    public function test_compounds_interest_matching_the_source_agreement(): void
    {
        $investor = Investor::create(['name' => 'Test Investor', 'status' => 'active']);
        $staffUser = User::first() ?? User::factory()->create();

        InvestmentRateSetting::create(['year' => 2024, 'annual_rate' => 17]);
        InvestmentRateSetting::create(['year' => 2025, 'annual_rate' => 15]);

        $service = app(InvestmentInterestService::class);

        // Second tranche: 91 days held in 2024 — matches the agreement's stated
        // day count and dollar figures to the cent.
        $investment2 = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'start_date' => '2024-10-02',
            'status' => 'active',
        ]);

        $draft2024 = $service->generateDraft($investment2, 2024);
        $this->assertSame(91, $this->daysFromDescription($draft2024->description));
        $this->assertEquals(847.67, (float) $draft2024->credit);
        $service->postDraft($draft2024, $staffUser);

        $draft2025 = $service->generateDraft($investment2->fresh(), 2025);
        $this->assertEquals(3127.15, (float) $draft2025->credit);
        $service->postDraft($draft2025, $staffUser);

        $this->assertEquals(23974.82, (float) $investment2->fresh()->current_balance);
    }

    private function daysFromDescription(string $description): int
    {
        preg_match('/x (\d+) days/', $description, $matches);

        return (int) ($matches[1] ?? 0);
    }
}

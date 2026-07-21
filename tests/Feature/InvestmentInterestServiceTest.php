<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\InvestmentRateSetting;
use App\Models\Investor;
use App\Models\User;
use App\Service\InvestmentInterestService;
use Carbon\Carbon;
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

    public function test_period_accrual_splits_across_a_calendar_year_boundary(): void
    {
        $investor = Investor::create(['name' => 'Boundary Investor', 'status' => 'active']);
        InvestmentRateSetting::create(['year' => 2024, 'annual_rate' => 17]);
        InvestmentRateSetting::create(['year' => 2025, 'annual_rate' => 15]);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $service = app(InvestmentInterestService::class);

        $accrual = $service->periodAccrual($investment, Carbon::parse('2024-12-01'), Carbon::parse('2025-01-31'), 10000);

        $this->assertCount(2, $accrual['segments']);
        $this->assertSame(2024, $accrual['segments'][0]['year']);
        $this->assertSame(31, $accrual['segments'][0]['days_held']);
        $this->assertEquals(17.0, $accrual['segments'][0]['rate']);
        $this->assertEquals(144.38, $accrual['segments'][0]['interest']);

        $this->assertSame(2025, $accrual['segments'][1]['year']);
        $this->assertSame(31, $accrual['segments'][1]['days_held']);
        $this->assertEquals(15.0, $accrual['segments'][1]['rate']);
        // Compounds on the segment-1 balance_end (10144.38), not the original 10000.
        $this->assertEquals(129.24, $accrual['segments'][1]['interest']);

        $this->assertEquals(273.62, $accrual['total_interest']);
        $this->assertEquals(10273.62, $accrual['balance_end']);
    }

    public function test_generate_draft_for_period_creates_one_row_per_segment_and_updates_cursor(): void
    {
        $investor = Investor::create(['name' => 'Cursor Investor', 'status' => 'active']);
        $staffUser = User::first() ?? User::factory()->create();
        InvestmentRateSetting::create(['year' => 2024, 'annual_rate' => 17]);
        InvestmentRateSetting::create(['year' => 2025, 'annual_rate' => 15]);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $service = app(InvestmentInterestService::class);

        $drafts = $service->generateDraftForPeriod($investment, Carbon::parse('2024-12-01'), Carbon::parse('2025-01-31'));
        $this->assertCount(2, $drafts);
        $this->assertTrue($drafts->every(fn ($draft) => $draft->posted === false));

        $service->postDraftBatch($drafts, $staffUser);

        $fresh = $investment->fresh();
        $this->assertEquals(10273.62, (float) $fresh->current_balance);
        $this->assertSame(2025, $fresh->last_interest_posted_year);
        $this->assertSame('2025-01-31', $fresh->last_interest_posted_through->toDateString());
    }

    public function test_generate_draft_rejects_year_when_partial_year_already_posted(): void
    {
        $investor = Investor::create(['name' => 'Partial Year Investor', 'status' => 'active']);
        $staffUser = User::first() ?? User::factory()->create();
        InvestmentRateSetting::create(['year' => 2025, 'annual_rate' => 15]);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $service = app(InvestmentInterestService::class);

        $drafts = $service->generateDraftForPeriod($investment, Carbon::parse('2025-01-01'), Carbon::parse('2025-03-31'));
        $service->postDraftBatch($drafts, $staffUser);

        $this->expectException(\RuntimeException::class);
        $service->generateDraft($investment->fresh(), 2025);
    }

    private function daysFromDescription(string $description): int
    {
        preg_match('/x (\d+) days/', $description, $matches);

        return (int) ($matches[1] ?? 0);
    }
}

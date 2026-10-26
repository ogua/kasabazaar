<?php

namespace Tests\Feature;

use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentRateSetting;
use App\Models\InvestmentTransaction;
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

    /**
     * Regression: once a year was part-posted (e.g. a Q1 posting), the year-end run used
     * to refuse it — and the scheduled job skipped it entirely because
     * last_interest_posted_year already matched — so Apr–Dec interest was never earned.
     */
    public function test_generate_draft_posts_the_rest_of_a_part_posted_year(): void
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
        $this->assertEquals(10369.86, (float) $investment->fresh()->current_balance);

        $remainder = $service->generateDraft($investment->fresh(), 2025);

        $this->assertSame('2025-04-01', $remainder->period_start->toDateString());
        $this->assertSame('2025-12-31', $remainder->period_end->toDateString());
        $this->assertSame(275, $this->daysFromDescription($remainder->description));
        $this->assertEquals(1171.94, (float) $remainder->credit);

        $service->postDraft($remainder, $staffUser);
        $this->assertEquals(11541.80, (float) $investment->fresh()->current_balance);
    }

    /**
     * The company posts interest at Dec 31: a yearly tranche opened in November earns
     * its pro-rated days at the first year end, then a full year after that.
     */
    public function test_year_end_command_drafts_pro_rated_interest_for_a_november_tranche(): void
    {
        $investor = Investor::create(['name' => 'November Investor', 'status' => 'active']);
        InvestmentRateSetting::create(['year' => 2026, 'annual_rate' => 10]);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2026-11-15',
            'status' => 'active',
        ]);

        $this->artisan('app:post-year-end-investment-interest', ['year' => 2026])->assertSuccessful();

        $draft = InvestmentTransaction::where('investment_id', $investment->id)->sole();

        $this->assertFalse($draft->posted);
        $this->assertSame('2026-12-31', $draft->date->toDateString());
        $this->assertSame('2026-11-15', $draft->period_start->toDateString());
        $this->assertSame(47, $this->daysFromDescription($draft->description));
        $this->assertEquals(128.77, (float) $draft->credit);
    }

    /**
     * Regression: loans pay cash interest on their payout schedule. The year-end job
     * used to draft compounding credits for them too, paying the same interest twice.
     */
    public function test_year_end_interest_is_never_drafted_or_posted_for_a_loan(): void
    {
        $investor = Investor::create(['name' => 'Year End Loan Investor', 'status' => 'active']);
        $staffUser = User::first() ?? User::factory()->create();
        InvestmentRateSetting::create(['year' => 2026, 'annual_rate' => 10]);

        $loan = Investment::create([
            'investor_id' => $investor->id,
            'capital_type' => 'loan',
            'principal_amount' => 40000,
            'start_date' => '2026-02-15',
            'payout_frequency' => 'quarterly',
            'status' => 'active',
        ]);

        $this->artisan('app:post-year-end-investment-interest', ['year' => 2026])->assertSuccessful();
        $this->assertSame(0, InvestmentTransaction::where('investment_id', $loan->id)->count());

        $staleDraft = InvestmentTransaction::create([
            'investment_id' => $loan->id,
            'investor_id' => $investor->id,
            'date' => '2026-12-31',
            'period_start' => '2026-02-15',
            'period_end' => '2026-12-31',
            'type' => InvestmentTransactionType::interest_credit->value,
            'op_balance' => 40000,
            'credit' => 3500,
            'year' => 2026,
            'posted' => false,
        ]);

        try {
            app(InvestmentInterestService::class)->postDraft($staleDraft, $staffUser);
            $this->fail('A loan interest credit must not be postable.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('is a loan', $e->getMessage());
        }

        $this->assertEquals(40000, (float) $loan->fresh()->current_balance);
    }

    /**
     * Regression: a partial withdrawal mid-year used to earn interest for the whole
     * year, and posting the year-end draft then reset the balance to the pre-withdrawal
     * figure — wiping the withdrawal off the books.
     */
    public function test_partial_withdrawal_stops_earning_and_survives_the_year_end_posting(): void
    {
        $investor = Investor::create(['name' => 'Partial Withdrawal Investor', 'status' => 'active']);
        $staffUser = User::first() ?? User::factory()->create();
        InvestmentRateSetting::create(['year' => 2025, 'annual_rate' => 10]);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $this->postWithdrawal($investment, '2025-07-01', 5000);

        $service = app(InvestmentInterestService::class);
        $draft = $service->generateDraft($investment->fresh(), 2025);

        // 181 days on 10,000 (Jan 1 – Jun 30) + 184 days on 5,000 (Jul 1 – Dec 31).
        $this->assertEquals(747.95, (float) $draft->credit);
        $this->assertStringContainsString('average balance', $draft->description);

        $service->postDraft($draft, $staffUser);

        $this->assertEquals(5747.95, (float) $investment->fresh()->current_balance);
        $this->assertEquals(5000.00, (float) $draft->fresh()->op_balance);
    }

    public function test_posting_keeps_a_withdrawal_recorded_after_the_draft_was_generated(): void
    {
        $investor = Investor::create(['name' => 'Late Withdrawal Investor', 'status' => 'active']);
        $staffUser = User::first() ?? User::factory()->create();
        InvestmentRateSetting::create(['year' => 2025, 'annual_rate' => 10]);
        InvestmentRateSetting::create(['year' => 2026, 'annual_rate' => 10]);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $service = app(InvestmentInterestService::class);
        $draft = $service->generateDraft($investment, 2025);
        $this->assertEquals(1000.00, (float) $draft->credit);

        // Paid out on Jan 1, before staff got round to approving the Dec 31 draft.
        $this->postWithdrawal($investment, '2026-01-01', 2000);

        $service->postDraft($draft, $staffUser);

        $this->assertEquals(9000.00, (float) $investment->fresh()->current_balance);
        $this->assertSame('2025-12-31', $investment->fresh()->last_interest_posted_through->toDateString());
    }

    /**
     * Regression: a year whose drafts were never posted used to be skipped for good —
     * the next year's run started at Jan 1 on the bare principal.
     */
    public function test_year_end_run_catches_up_a_year_that_was_never_posted(): void
    {
        $investor = Investor::create(['name' => 'Missed Year Investor', 'status' => 'active']);
        $staffUser = User::first() ?? User::factory()->create();
        InvestmentRateSetting::create(['year' => 2024, 'annual_rate' => 17]);
        InvestmentRateSetting::create(['year' => 2025, 'annual_rate' => 15]);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'start_date' => '2024-10-02',
            'status' => 'active',
        ]);

        $service = app(InvestmentInterestService::class);
        $drafts = $service->generateYearEndDrafts($investment, 2025);

        $this->assertSame([2024, 2025], $drafts->pluck('year')->all());
        $this->assertSame([847.67, 3127.15], $drafts->map(fn ($draft) => (float) $draft->credit)->all());

        $service->postDraftBatch($drafts, $staffUser);

        // Same figures as the signed agreement posted one year at a time.
        $this->assertEquals(23974.82, (float) $investment->fresh()->current_balance);
    }

    private function postWithdrawal(Investment $investment, string $date, float $amount): void
    {
        InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'date' => $date,
            'type' => InvestmentTransactionType::withdrawal->value,
            'op_balance' => $investment->fresh()->current_balance,
            'debit' => $amount,
            'posted' => true,
            'posted_at' => now(),
        ]);

        $investment->update(['current_balance' => round((float) $investment->fresh()->current_balance - $amount, 2)]);
    }

    public function test_segmented_valuation_matches_valuation_as_of_and_breaks_down_by_segment(): void
    {
        $investor = Investor::create(['name' => 'Segmented Valuation Investor', 'status' => 'active']);
        $staffUser = User::first() ?? User::factory()->create();
        InvestmentRateSetting::create(['year' => 2024, 'annual_rate' => 17]);
        InvestmentRateSetting::create(['year' => 2025, 'annual_rate' => 15]);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'start_date' => '2024-10-02',
            'status' => 'active',
        ]);

        $service = app(InvestmentInterestService::class);

        $service->postDraft($service->generateDraft($investment, 2024), $staffUser);

        $asOfDate = Carbon::parse('2025-12-31');
        $segmented = $service->segmentedValuationAsOf($investment->fresh(), $asOfDate);
        $valuation = $service->valuationAsOf($investment->fresh(), $asOfDate);

        $this->assertCount(2, $segmented['segments']);

        // Already-posted 2024 segment, sourced from the transaction row.
        $this->assertSame(2024, $segmented['segments'][0]['year']);
        $this->assertSame(91, $segmented['segments'][0]['days_held']);
        $this->assertEquals(17.0, $segmented['segments'][0]['rate']);
        $this->assertEquals(847.67, $segmented['segments'][0]['interest']);

        // Trailing, not-yet-posted 2025 segment computed live via periodAccrual.
        $this->assertSame(2025, $segmented['segments'][1]['year']);
        $this->assertEquals(15.0, $segmented['segments'][1]['rate']);
        $this->assertEquals(3127.15, $segmented['segments'][1]['interest']);

        $this->assertEquals($valuation['interest_earned_total'], $segmented['interest_earned_total']);
        $this->assertEquals($valuation['compounded_balance'], $segmented['compounded_balance']);
    }

    private function daysFromDescription(string $description): int
    {
        preg_match('/x (\d+) days/', $description, $matches);

        return (int) ($matches[1] ?? 0);
    }
}

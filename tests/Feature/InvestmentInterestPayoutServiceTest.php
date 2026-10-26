<?php

namespace Tests\Feature;

use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentRateOverride;
use App\Models\Investor;
use App\Models\User;
use App\Service\InvestmentInterestPayoutService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestmentInterestPayoutServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function makeLoan(Investor $investor, array $overrides = []): Investment
    {
        return Investment::create(array_merge([
            'investor_id' => $investor->id,
            'capital_type' => 'loan',
            'principal_amount' => 40000,
            'start_date' => '2026-02-15',
            'contract_term_months' => 12,
            'payout_frequency' => 'quarterly',
            'status' => 'active',
        ], $overrides));
    }

    public function test_generate_due_never_changes_the_investments_current_balance(): void
    {
        $investor = Investor::create(['name' => 'Loan Investor', 'status' => 'active']);
        $loan = $this->makeLoan($investor);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $service = app(InvestmentInterestPayoutService::class);
        $payout = $service->generateDue(
            $loan,
            \Carbon\Carbon::parse('2026-02-15'),
            \Carbon\Carbon::parse('2026-05-15'),
            \Carbon\Carbon::parse('2026-05-15')
        );

        $this->assertSame('due', $payout->status->value);
        $this->assertGreaterThan(0, (float) $payout->amount);
        $this->assertEquals(40000.00, (float) $loan->fresh()->current_balance);
    }

    public function test_record_payment_pays_out_cash_without_touching_principal_balance(): void
    {
        $investor = Investor::create(['name' => 'Paid Loan Investor', 'status' => 'active']);
        $staff = User::first() ?? User::factory()->create();
        $loan = $this->makeLoan($investor);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $service = app(InvestmentInterestPayoutService::class);
        $payout = $service->generateDue(
            $loan,
            \Carbon\Carbon::parse('2026-02-15'),
            \Carbon\Carbon::parse('2026-05-15'),
            \Carbon\Carbon::parse('2026-05-15')
        );

        $service->recordPayment($payout, null, $staff, ['payout_gateway' => 'manual', 'payment_reference' => 'REF1']);

        $payout->refresh();
        $this->assertSame('paid', $payout->status->value);
        $this->assertEquals((float) $payout->amount, (float) $payout->amount_paid);
        $this->assertEquals(40000.00, (float) $loan->fresh()->current_balance, 'A loan\'s principal balance must never compound from an interest payout.');

        $txn = $loan->transactions()->where('type', InvestmentTransactionType::interest_payout->value)->first();
        $this->assertNotNull($txn);
        $this->assertEquals((float) $txn->debit, (float) $txn->credit, 'debit must equal credit so cl_balance collapses back to op_balance.');
    }

    public function test_reverse_payout_records_an_offsetting_entry_and_marks_reversed(): void
    {
        $investor = Investor::create(['name' => 'Reversed Loan Investor', 'status' => 'active']);
        $staff = User::first() ?? User::factory()->create();
        $loan = $this->makeLoan($investor);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $service = app(InvestmentInterestPayoutService::class);
        $payout = $service->generateDue(
            $loan,
            \Carbon\Carbon::parse('2026-02-15'),
            \Carbon\Carbon::parse('2026-05-15'),
            \Carbon\Carbon::parse('2026-05-15')
        );
        $service->recordPayment($payout, null, $staff, ['payout_gateway' => 'manual']);
        $service->reversePayout($payout->fresh(), $staff, 'Wrong investment credited');

        $payout->refresh();
        $this->assertSame('reversed', $payout->status->value);
        $this->assertEquals(40000.00, (float) $loan->fresh()->current_balance);

        $reversal = $loan->transactions()
            ->where('type', InvestmentTransactionType::interest_payout->value)
            ->where('debit', '<', 0)
            ->first();
        $this->assertNotNull($reversal);
    }

    /**
     * Regression: some loans only disburse interest at contract maturity, with
     * interest simply accruing (recorded as "due") each period until then. If a
     * period gets mistakenly marked paid, revertToDue() must cancel the phantom cash
     * movement and land on 'due' — not 'reversed' — so the amount still shows as
     * earned/owed rather than looking like a cancelled/undone payment.
     */
    public function test_revert_to_due_cancels_a_mistaken_paid_mark_without_touching_balance(): void
    {
        $investor = Investor::create(['name' => 'Revert To Due Investor', 'status' => 'active']);
        $staff = User::first() ?? User::factory()->create();
        $loan = $this->makeLoan($investor);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $service = app(InvestmentInterestPayoutService::class);
        $payout = $service->generateDue(
            $loan,
            \Carbon\Carbon::parse('2026-02-15'),
            \Carbon\Carbon::parse('2026-05-15'),
            \Carbon\Carbon::parse('2026-05-15')
        );
        $service->recordPayment($payout, null, $staff, ['payout_gateway' => 'manual', 'payment_reference' => 'MISTAKE']);

        $reverted = $service->revertToDue($payout->fresh(), $staff, 'Not actually paid — payout deferred to maturity.');

        $this->assertSame('due', $reverted->status->value);
        $this->assertEquals(900.0, round((float) $reverted->amount, 2));
        $this->assertEquals(0.0, (float) $reverted->amount_paid);
        $this->assertNull($reverted->payout_gateway);
        $this->assertEquals(40000.00, (float) $loan->fresh()->current_balance);

        $netMovement = $loan->transactions()
            ->where('type', InvestmentTransactionType::interest_payout->value)
            ->sum('debit');
        $this->assertEquals(0.0, (float) $netMovement, 'The phantom payment and its correction must net to zero.');
    }

    public function test_reverse_payout_is_rejected_for_a_due_row_not_yet_paid(): void
    {
        $investor = Investor::create(['name' => 'Due Loan Investor', 'status' => 'active']);
        $staff = User::first() ?? User::factory()->create();
        $loan = $this->makeLoan($investor);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $service = app(InvestmentInterestPayoutService::class);
        $payout = $service->generateDue(
            $loan,
            \Carbon\Carbon::parse('2026-02-15'),
            \Carbon\Carbon::parse('2026-05-15'),
            \Carbon\Carbon::parse('2026-05-15')
        );

        $this->expectException(\InvalidArgumentException::class);
        $service->reversePayout($payout, $staff, 'should not be allowed');
    }

    public function test_project_schedule_matches_the_command_generated_amounts(): void
    {
        $investor = Investor::create(['name' => 'Schedule Investor', 'status' => 'active']);
        $loan = $this->makeLoan($investor);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $service = app(InvestmentInterestPayoutService::class);
        $schedule = $service->projectSchedule($loan->fresh());

        $this->assertCount(4, $schedule, 'A 12-month quarterly loan should project exactly 4 payout periods.');
        $this->assertEquals($loan->maturity_date->toDateString(), $schedule[3]['period_end']->toDateString());

        $generated = $service->generateDue($loan, $schedule[0]['period_start'], $schedule[0]['period_end'], $schedule[0]['due_date']);
        $this->assertEquals($schedule[0]['amount'], (float) $generated->amount, 'The projected schedule must match what generateDue() actually produces.');
    }

    /**
     * Regression: a $40,000 loan at 9% quarterly must produce exactly the schedule
     * written into the signed physical agreement — flat $900 every quarter on the
     * 15th of Feb/May/Aug/Nov, with the final period clamped to the maturity date
     * (Feb 14, the day before the anniversary) rather than day-counted, uneven
     * amounts landing a day or two later each quarter.
     */
    public function test_project_schedule_matches_a_flat_quarterly_loan_agreement_exactly(): void
    {
        $investor = Investor::create(['name' => 'Flat Schedule Investor', 'status' => 'active']);
        $loan = $this->makeLoan($investor, ['maturity_date' => '2027-02-14']);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $schedule = app(InvestmentInterestPayoutService::class)->projectSchedule($loan->fresh());

        $this->assertCount(4, $schedule);
        $this->assertSame(['2026-05-15', '2026-08-15', '2026-11-15', '2027-02-14'], array_map(
            fn (array $row) => $row['due_date']->toDateString(),
            $schedule
        ));
        $this->assertSame([900.0, 900.0, 900.0, 900.0], array_map(fn (array $row) => $row['amount'], $schedule));
        $this->assertEqualsWithDelta(3600.0, array_sum(array_column($schedule, 'amount')), 0.001);
    }

    public function test_record_payment_rejects_zero_negative_and_overpaid_amounts(): void
    {
        $investor = Investor::create(['name' => 'Overpaid Loan Investor', 'status' => 'active']);
        $staff = User::first() ?? User::factory()->create();
        $loan = $this->makeLoan($investor);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $service = app(InvestmentInterestPayoutService::class);
        $payout = $service->generateDue($loan, Carbon::parse('2026-02-15'), Carbon::parse('2026-05-15'), Carbon::parse('2026-05-15'));

        foreach ([0, -50, 900.50] as $amount) {
            try {
                $service->recordPayment($payout->fresh(), $amount, $staff, ['payout_gateway' => 'manual']);
                $this->fail("An amount of {$amount} against a \$900 payout must be refused.");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('still owed', $e->getMessage());
            }
        }

        $service->recordPayment($payout->fresh(), 400, $staff, ['payout_gateway' => 'manual']);
        $this->assertSame('processing', $payout->fresh()->status->value);

        $this->expectException(\InvalidArgumentException::class);
        $service->recordPayment($payout->fresh(), 600, $staff, ['payout_gateway' => 'manual']);
    }

    /**
     * Regression: "Record Payment" stayed available while a Paystack transfer was in
     * flight, so a manual entry plus the transfer confirmation paid the lender twice.
     */
    public function test_manual_payment_is_refused_while_a_paystack_transfer_is_in_flight(): void
    {
        $investor = Investor::create(['name' => 'In Flight Loan Investor', 'status' => 'active']);
        $staff = User::first() ?? User::factory()->create();
        $loan = $this->makeLoan($investor);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $service = app(InvestmentInterestPayoutService::class);
        $payout = $service->generateDue($loan, Carbon::parse('2026-02-15'), Carbon::parse('2026-05-15'), Carbon::parse('2026-05-15'));
        $payout->update(['status' => 'processing', 'payout_gateway' => 'paystack', 'paystack_transfer_code' => 'TRF_LOAN_1']);

        try {
            $service->recordPayment($payout->fresh(), null, $staff, ['payout_gateway' => 'manual', 'payment_reference' => 'BANK-1']);
            $this->fail('A manual payment must not be recorded on top of an in-flight transfer.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('still in progress', $e->getMessage());
        }

        // The transfer's own confirmation is still accepted.
        $service->recordPayment($payout->fresh(), null, $staff, ['payout_gateway' => 'paystack', 'payment_reference' => 'TRF_LOAN_1']);

        $this->assertSame('paid', $payout->fresh()->status->value);
        $this->assertEquals(900.0, (float) $payout->fresh()->amount_paid);
    }

    /**
     * Regression: due dates were advanced with overflowing month arithmetic, so a loan
     * started on Nov 30 fell due on Mar 2 instead of Feb 28 and drifted further from
     * the agreement every period.
     */
    public function test_month_end_loan_falls_due_on_the_last_day_of_shorter_months(): void
    {
        $investor = Investor::create(['name' => 'Month End Investor', 'status' => 'active']);
        $loan = $this->makeLoan($investor, ['start_date' => '2026-11-30']);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $this->assertSame('2027-02-28', $loan->next_payout_due_date->toDateString());
        $this->assertSame(['2027-02-28', '2027-05-30', '2027-08-30', '2027-11-30'], array_map(
            fn (array $row) => $row['due_date']->toDateString(),
            app(InvestmentInterestPayoutService::class)->projectSchedule($loan->fresh())
        ));
    }

    public function test_daily_job_catches_up_missed_quarters_without_duplicating_them(): void
    {
        $investor = Investor::create(['name' => 'Catch Up Investor', 'status' => 'active']);
        $loan = $this->makeLoan($investor, ['maturity_date' => '2027-02-14']);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $service = app(InvestmentInterestPayoutService::class);

        // The job did not run in May or August.
        $created = $service->generateDuePayouts($loan->fresh(), Carbon::parse('2026-09-01'));

        $this->assertSame(['2026-05-15', '2026-08-15'], $created->map(fn ($payout) => $payout->due_date->toDateString())->all());
        $this->assertSame([900.0, 900.0], $created->map(fn ($payout) => (float) $payout->amount)->all());
        $this->assertSame('2026-11-15', $loan->fresh()->next_payout_due_date->toDateString());

        $this->assertCount(0, $service->generateDuePayouts($loan->fresh(), Carbon::parse('2026-09-02')));
        $this->assertSame(2, $loan->interestPayouts()->count());
    }

    /**
     * Loan principal is due in full at maturity. The daily job now opens the repayment
     * in the staff withdrawal queue once all four quarters are generated, exactly once.
     */
    public function test_matured_loan_gets_its_final_payout_and_a_principal_repayment_request(): void
    {
        $investor = Investor::create(['name' => 'Matured Loan Investor', 'status' => 'active']);
        $loan = $this->makeLoan($investor, [
            'start_date' => now()->subYear()->subDays(2)->toDateString(),
            'current_balance' => 40000,
        ]);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => $loan->start_date->year, 'annual_rate' => 9]);

        $this->artisan('app:generate-investment-interest-payout-drafts')->assertSuccessful();
        $this->artisan('app:generate-investment-interest-payout-drafts')->assertSuccessful();

        $loan->refresh();
        $this->assertSame(4, $loan->interestPayouts()->count());
        $this->assertNull($loan->next_payout_due_date);

        $request = $loan->withdrawalRequests()->sole();
        $this->assertTrue($request->is_full_withdrawal);
        $this->assertSame('submitted', $request->status->value);
        $this->assertStringContainsString('principal repayment due', $request->notes);
    }
}

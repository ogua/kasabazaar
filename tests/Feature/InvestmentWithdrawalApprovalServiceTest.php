<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\InvestmentRateOverride;
use App\Models\InvestmentRateSetting;
use App\Models\Investor;
use App\Models\User;
use App\Service\InvestmentInterestPayoutService;
use App\Services\InvestmentWithdrawalApprovalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestmentWithdrawalApprovalServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_partial_withdrawal_reduces_balance_without_closing_investment(): void
    {
        $investor = Investor::create(['name' => 'Partial Withdrawal Investor', 'status' => 'active']);
        $staff = User::first() ?? User::factory()->create();
        $service = app(InvestmentWithdrawalApprovalService::class);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'current_balance' => 20000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $request = $service->submit($investment, ['is_full_withdrawal' => false, 'requested_amount' => 6000]);
        $service->markUnderReview($request);
        $request = $service->approve($request->fresh(), $staff, []);
        $request = $service->recordPayment($request->fresh(), 6000, $staff, [
            'payout_gateway' => 'manual',
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'REF1',
        ]);

        $this->assertSame('paid', $request->status->value);
        $this->assertEquals(14000, (float) $investment->fresh()->current_balance);
        $this->assertSame('active', $investment->fresh()->status->value);
    }

    public function test_partial_withdrawal_payment_cannot_exceed_the_requested_amount(): void
    {
        $investor = Investor::create(['name' => 'Overpaid Withdrawal Investor', 'status' => 'active']);
        $staff = User::first() ?? User::factory()->create();
        $service = app(InvestmentWithdrawalApprovalService::class);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'current_balance' => 20000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $request = $service->submit($investment, ['is_full_withdrawal' => false, 'requested_amount' => 6000]);
        $request = $service->approve($request->fresh(), $staff, []);

        try {
            $service->recordPayment($request->fresh(), 16000, $staff, ['payout_gateway' => 'manual']);
            $this->fail('Paying more than was requested must be refused.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('still owed', $e->getMessage());
        }

        $this->assertEquals(20000, (float) $investment->fresh()->current_balance);
    }

    public function test_manual_payment_is_refused_while_a_paystack_withdrawal_transfer_is_in_flight(): void
    {
        $investor = Investor::create(['name' => 'In Flight Withdrawal Investor', 'status' => 'active']);
        $staff = User::first() ?? User::factory()->create();
        $service = app(InvestmentWithdrawalApprovalService::class);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'current_balance' => 20000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $request = $service->submit($investment, ['is_full_withdrawal' => false, 'requested_amount' => 6000]);
        $request = $service->approve($request->fresh(), $staff, []);
        $request->update(['status' => 'processing', 'payout_gateway' => 'paystack', 'paystack_transfer_code' => 'TRF_WD_1']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('still in progress');

        $service->recordPayment($request->fresh(), 6000, $staff, ['payout_gateway' => 'manual', 'payment_reference' => 'BANK-2']);
    }

    /**
     * Loan principal is due on the maturity date with any accrued interest. Approval
     * is refused while interest is unpaid (generating the final quarter first), and
     * once approved the principal is due at once rather than after the 60-day window.
     */
    public function test_loan_principal_repayment_requires_settled_interest_and_is_due_immediately(): void
    {
        $investor = Investor::create(['name' => 'Principal Repayment Investor', 'status' => 'active']);
        $staff = User::first() ?? User::factory()->create();
        $service = app(InvestmentWithdrawalApprovalService::class);

        $loan = Investment::create([
            'investor_id' => $investor->id,
            'capital_type' => 'loan',
            'principal_amount' => 40000,
            'current_balance' => 40000,
            'start_date' => now()->subYear()->subDays(2)->toDateString(),
            'contract_term_months' => 12,
            'payout_frequency' => 'quarterly',
            'status' => 'active',
        ]);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => $loan->start_date->year, 'annual_rate' => 9]);

        $request = $service->requestLoanPrincipalRepayment($loan->fresh());

        try {
            $service->approve($request->fresh(), $staff, []);
            $this->fail('Principal must not be approved while interest is unpaid.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('4 unpaid interest payout(s) totalling $3,600.00', $e->getMessage());
        }

        $payoutService = app(InvestmentInterestPayoutService::class);
        foreach ($loan->interestPayouts()->get() as $payout) {
            $payoutService->recordPayment($payout, null, $staff, ['payout_gateway' => 'manual']);
        }

        $request = $service->approve($request->fresh(), $staff, []);
        $this->assertTrue($request->payment_due_by->isToday());

        $request = $service->recordPayment($request->fresh(), null, $staff, ['payout_gateway' => 'manual']);

        $this->assertSame('paid', $request->status->value);
        $this->assertEquals(40000.0, (float) $request->amount_paid);
        $this->assertSame('withdrawn', $loan->fresh()->status->value);
        $this->assertEquals(0.0, (float) $loan->fresh()->current_balance);
    }

    public function test_partial_withdrawal_below_minimum_remaining_balance_is_rejected(): void
    {
        $investor = Investor::create(['name' => 'Rejected Withdrawal Investor', 'status' => 'active']);
        $service = app(InvestmentWithdrawalApprovalService::class);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'current_balance' => 20000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $service->submit($investment, ['is_full_withdrawal' => false, 'requested_amount' => 12000]);
    }

    /**
     * Regression: a full withdrawal must true-up accrued interest through today before
     * paying out, and use that fresh balance as the payout amount — not a stale figure
     * computed before the true-up ran. Previously the investment was left with a
     * nonzero balance and the request never reached 'paid'.
     */
    public function test_full_withdrawal_pays_out_the_freshly_trued_up_balance(): void
    {
        InvestmentRateSetting::firstOrCreate(['year' => now()->year], ['annual_rate' => 10]);
        // Tranches start in 2025, so the full-withdrawal true-up accrues 2025 too.
        InvestmentRateSetting::firstOrCreate(['year' => 2025], ['annual_rate' => 10]);

        $investor = Investor::create(['name' => 'Full Withdrawal Investor', 'status' => 'active']);
        $staff = User::first() ?? User::factory()->create();
        $service = app(InvestmentWithdrawalApprovalService::class);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 15000,
            'current_balance' => 15000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $request = $service->submit($investment, ['is_full_withdrawal' => true]);
        $request = $service->approve($request->fresh(), $staff, []);

        // Pass no amount (mirrors the Filament action, which disables manual entry for
        // full withdrawals since the true figure isn't known until the true-up runs).
        $request = $service->recordPayment($request->fresh(), null, $staff, [
            'payout_gateway' => 'paystack',
            'payment_reference' => 'TRF_123',
        ]);

        $this->assertSame('paid', $request->status->value);
        $this->assertSame('withdrawn', $investment->fresh()->status->value);
        $this->assertEquals(0.0, (float) $investment->fresh()->current_balance);
        $this->assertGreaterThan(15000, (float) $request->amount_paid);
    }

    public function test_withdrawal_request_is_rejected_before_the_contract_is_due(): void
    {
        $investor = Investor::create(['name' => 'Premature Withdrawal Investor', 'status' => 'active']);
        $service = app(InvestmentWithdrawalApprovalService::class);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'current_balance' => 20000,
            'start_date' => now()->subMonths(3), // 12-month default term: 9 months remain
            'status' => 'active',
        ]);

        $this->assertFalse($investment->isContractDue());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('contract is not yet due');

        $service->submit($investment, ['is_full_withdrawal' => true]);
    }

    public function test_withdrawal_request_is_allowed_once_a_shorter_contract_term_matures(): void
    {
        $investor = Investor::create(['name' => 'Short Term Investor', 'status' => 'active']);
        $service = app(InvestmentWithdrawalApprovalService::class);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'current_balance' => 20000,
            'start_date' => now()->subMonths(7),
            'contract_term_months' => 6,
            'status' => 'active',
        ]);

        $this->assertTrue($investment->isContractDue());

        $request = $service->submit($investment, ['is_full_withdrawal' => false, 'requested_amount' => 6000]);

        $this->assertSame('submitted', $request->status->value);
    }

    /**
     * Regression: a loan's signed terms only allow the Company to prepay — the lender
     * has no contractual right to demand early return of principal. Even once the
     * contract term has elapsed (which used to be the only gate), a loan-type
     * investment must still be rejected for a self-service withdrawal request.
     */
    public function test_loan_investment_is_rejected_for_withdrawal(): void
    {
        $investor = Investor::create(['name' => 'Loan Withdrawal Investor', 'status' => 'active']);
        $service = app(InvestmentWithdrawalApprovalService::class);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'capital_type' => 'loan',
            'principal_amount' => 40000,
            'current_balance' => 40000,
            'start_date' => now()->subMonths(13),
            'contract_term_months' => 12,
            'payout_frequency' => 'quarterly',
            'status' => 'active',
        ]);

        $this->assertTrue($investment->isContractDue());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not eligible for early withdrawal');

        $service->submit($investment, ['is_full_withdrawal' => true]);
    }
}

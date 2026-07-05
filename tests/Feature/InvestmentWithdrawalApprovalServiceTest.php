<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\InvestmentRateSetting;
use App\Models\Investor;
use App\Models\User;
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
}

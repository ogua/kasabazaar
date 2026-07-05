<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\InvestmentRateSetting;
use App\Models\Investor;
use App\Models\User;
use App\Service\InvestmentTransferService;
use App\Services\InvestmentWithdrawalApprovalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvestmentTransferServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_full_withdrawal_transfer_success_webhook_completes_the_payout(): void
    {
        InvestmentRateSetting::firstOrCreate(['year' => now()->year], ['annual_rate' => 10]);

        $investor = Investor::create([
            'name' => 'Transfer Test Investor',
            'status' => 'active',
            'bank_code' => '057',
            'account_number' => '0123456789',
            'account_name' => 'Transfer Test Investor',
        ]);
        $staff = User::first() ?? User::factory()->create();

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'current_balance' => 10000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $withdrawalService = app(InvestmentWithdrawalApprovalService::class);
        $request = $withdrawalService->submit($investment, ['is_full_withdrawal' => true]);
        $request = $withdrawalService->approve($request->fresh(), $staff, []);

        Http::fake([
            '*/transferrecipient' => Http::response([
                'status' => true,
                'data' => ['recipient_code' => 'RCP_123'],
            ]),
            '*/transfer' => Http::response([
                'status' => true,
                'data' => ['transfer_code' => 'TRF_abc123'],
            ]),
        ]);

        $transferService = app(InvestmentTransferService::class);
        $transferService->initiatePaystackTransfer($request->fresh(), $staff);

        $request->refresh();
        $this->assertSame('processing', $request->status->value);
        $this->assertSame('TRF_abc123', $request->paystack_transfer_code);
        $this->assertSame('RCP_123', $investor->fresh()->paystack_recipient_code);

        // Simulate the transfer.success webhook firing.
        $transferService->handleTransferSuccess('TRF_abc123');

        $request->refresh();
        $investment->refresh();
        $this->assertSame('paid', $request->status->value);
        $this->assertSame('withdrawn', $investment->status->value);
        $this->assertEquals(0.0, (float) $investment->current_balance);

        // Idempotent: firing the webhook again does not double-process.
        $transferService->handleTransferSuccess('TRF_abc123');
        $this->assertEquals(0.0, (float) $investment->fresh()->current_balance);
    }

    public function test_failed_transfer_reverts_request_to_approved_for_retry(): void
    {
        InvestmentRateSetting::firstOrCreate(['year' => now()->year], ['annual_rate' => 10]);

        $investor = Investor::create([
            'name' => 'Failed Transfer Investor',
            'status' => 'active',
            'bank_code' => '057',
            'account_number' => '9876543210',
        ]);
        $staff = User::first() ?? User::factory()->create();

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 3000,
            'current_balance' => 3000,
            'start_date' => '2025-06-01',
            'status' => 'active',
        ]);

        $withdrawalService = app(InvestmentWithdrawalApprovalService::class);
        $request = $withdrawalService->submit($investment, ['is_full_withdrawal' => true]);
        $request = $withdrawalService->approve($request->fresh(), $staff, []);

        Http::fake([
            '*/transferrecipient' => Http::response(['status' => true, 'data' => ['recipient_code' => 'RCP_999']]),
            '*/transfer' => Http::response(['status' => true, 'data' => ['transfer_code' => 'TRF_fail']]),
        ]);

        app(InvestmentTransferService::class)->initiatePaystackTransfer($request->fresh(), $staff);
        $this->assertSame('processing', $request->fresh()->status->value);

        app(InvestmentTransferService::class)->handleTransferFailed('TRF_fail');

        $this->assertSame('approved', $request->fresh()->status->value);
    }
}

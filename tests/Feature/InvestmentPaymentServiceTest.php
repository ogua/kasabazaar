<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\Investor;
use App\Service\InvestmentPaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvestmentPaymentServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manual_deposit_activates_investment_and_creates_contribution_ledger_row(): void
    {
        $investor = Investor::create(['name' => 'Manual Deposit Investor', 'status' => 'active']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 8000,
            'start_date' => now()->toDateString(),
            'deposit_gateway' => 'manual',
        ]);

        app(InvestmentPaymentService::class)->recordManualDeposit($investment, [
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'WIRE-999',
        ]);

        $investment->refresh();

        $this->assertSame('active', $investment->status->value);
        $this->assertEquals(1, $investment->transactions()->where('type', 'contribution')->count());
        $this->assertEquals(8000, (float) $investment->transactions()->first()->credit);
    }

    public function test_paystack_deposit_is_verified_and_activates_investment(): void
    {
        $investor = Investor::create(['name' => 'Paystack Deposit Investor', 'status' => 'active', 'email' => 'investor@example.com']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'start_date' => now()->toDateString(),
        ]);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/abc123',
                    'reference' => 'PSK-REF-1',
                    'access_code' => 'access123',
                ],
            ]),
            '*/transaction/verify/*' => Http::response([
                'data' => [
                    'status' => 'success',
                    'metadata' => ['type' => 'investment_deposit', 'investment_id' => $investment->id],
                ],
            ]),
        ]);

        $service = app(InvestmentPaymentService::class);

        $initiation = $service->initiatePaystack($investment, $investor->email);
        $this->assertSame('PSK-REF-1', $initiation['reference']);
        $this->assertSame('paystack', $investment->fresh()->deposit_gateway);
        $this->assertSame('pending_payment', $investment->fresh()->status->value);

        $verified = $service->verifyAndRecordPaystack('PSK-REF-1');
        $this->assertSame('active', $verified->status->value);
        $this->assertEquals(1, $verified->transactions()->where('type', 'contribution')->count());

        // Idempotent: verifying again does not duplicate the contribution row.
        $service->verifyAndRecordPaystack('PSK-REF-1');
        $this->assertEquals(1, $investment->fresh()->transactions()->where('type', 'contribution')->count());
    }
}

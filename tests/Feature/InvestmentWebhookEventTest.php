<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\InvestmentRateSetting;
use App\Models\InvestmentWebhookEvent;
use App\Models\Investor;
use App\Models\User;
use App\Service\InvestmentTransferService;
use App\Services\InvestmentWithdrawalApprovalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvestmentWebhookEventTest extends TestCase
{
    use DatabaseTransactions;

    public function test_paystack_webhook_records_a_processed_event_on_successful_charge(): void
    {
        config(['services.paystack.secret_key' => 'test-secret']);

        $investor = Investor::create(['name' => 'Webhook Paystack Investor', 'status' => 'active']);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'start_date' => now()->toDateString(),
        ]);

        Http::fake([
            '*/transaction/verify/*' => Http::response([
                'data' => [
                    'status' => 'success',
                    'metadata' => ['type' => 'investment_deposit', 'investment_id' => $investment->id],
                ],
            ]),
        ]);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'WEBHOOK-REF-1',
                'metadata' => ['type' => 'investment_deposit', 'investment_id' => $investment->id],
            ],
        ]);
        $signature = hash_hmac('sha512', $payload, 'test-secret');

        $response = $this->call('POST', '/api/v1/investments/webhooks/paystack', [], [], [], [
            'HTTP_x-paystack-signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        $event = InvestmentWebhookEvent::where('reference', 'WEBHOOK-REF-1')->first();
        $this->assertNotNull($event);
        $this->assertSame('processed', $event->status->value);
        $this->assertSame($investment->id, $event->investment_id);
        $this->assertSame('active', $investment->fresh()->status->value);
    }

    public function test_paystack_webhook_records_an_ignored_event_for_non_investment_metadata(): void
    {
        config(['services.paystack.secret_key' => 'test-secret']);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'WEBHOOK-REF-IGNORED',
                'metadata' => ['type' => 'something_else'],
            ],
        ]);
        $signature = hash_hmac('sha512', $payload, 'test-secret');

        $response = $this->call('POST', '/api/v1/investments/webhooks/paystack', [], [], [], [
            'HTTP_x-paystack-signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        $event = InvestmentWebhookEvent::where('reference', 'WEBHOOK-REF-IGNORED')->first();
        $this->assertNotNull($event);
        $this->assertSame('ignored', $event->status->value);
    }

    public function test_paystack_webhook_records_a_failed_event_when_verification_throws(): void
    {
        config(['services.paystack.secret_key' => 'test-secret']);

        Http::fake([
            '*/transaction/verify/*' => Http::response(['data' => ['status' => 'failed']]),
        ]);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'WEBHOOK-REF-FAIL',
                'metadata' => ['type' => 'investment_deposit', 'investment_id' => (string) \Illuminate\Support\Str::uuid()],
            ],
        ]);
        $signature = hash_hmac('sha512', $payload, 'test-secret');

        $response = $this->call('POST', '/api/v1/investments/webhooks/paystack', [], [], [], [
            'HTTP_x-paystack-signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        $event = InvestmentWebhookEvent::where('reference', 'WEBHOOK-REF-FAIL')->first();
        $this->assertNotNull($event);
        $this->assertSame('failed', $event->status->value);
        $this->assertNotEmpty($event->error_message);
    }

    public function test_paystack_transfer_webhook_records_a_processed_event_and_links_withdrawal_request(): void
    {
        config(['services.paystack.secret_key' => 'test-secret']);
        InvestmentRateSetting::firstOrCreate(['year' => now()->year], ['annual_rate' => 10]);

        $investor = Investor::create([
            'name' => 'Webhook Transfer Investor',
            'status' => 'active',
            'bank_code' => '057',
            'account_number' => '0123456789',
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
            '*/transferrecipient' => Http::response(['status' => true, 'data' => ['recipient_code' => 'RCP_WEBHOOK']]),
            '*/transfer' => Http::response(['status' => true, 'data' => ['transfer_code' => 'TRF-WEBHOOK-1']]),
        ]);

        app(InvestmentTransferService::class)->initiatePaystackTransfer($request->fresh(), $staff);
        $this->assertSame('processing', $request->fresh()->status->value);

        $payload = json_encode([
            'event' => 'transfer.success',
            'data' => ['transfer_code' => 'TRF-WEBHOOK-1'],
        ]);
        $signature = hash_hmac('sha512', $payload, 'test-secret');

        $response = $this->call('POST', '/api/v1/investments/webhooks/paystack-transfer', [], [], [], [
            'HTTP_x-paystack-signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        $event = InvestmentWebhookEvent::where('reference', 'TRF-WEBHOOK-1')->first();
        $this->assertNotNull($event);
        $this->assertSame('processed', $event->status->value);
        $this->assertSame($request->id, $event->investment_withdrawal_request_id);
        $this->assertSame('paid', $request->fresh()->status->value);
    }
}

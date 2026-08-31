<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicShipmentPaymentTest extends TestCase
{
    use DatabaseTransactions;

    private function makeShipment(float $total = 500, float $rate = 12): Shipment
    {
        $client = Client::create([
            'branch_id' => (string) Str::uuid(),
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'phone' => '+233201234567',
        ]);

        return Shipment::create([
            'client_id' => $client->id,
            'branch_id' => $client->branch_id,
            'origin_branch_id' => 'US-WAREHOUSE',
            'destination_branch_id' => 'ACCRA',
            'status' => 'pending',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'exchange_rate_at_shipment' => $rate,
            'total' => $total,
        ]);
    }

    public function test_paying_more_than_the_balance_is_rejected(): void
    {
        $shipment = $this->makeShipment(500);

        $response = $this->from(route('public-shipment-view', $shipment->public_view_token))
            ->post(route('public-shipment-pay', $shipment->public_view_token), ['amount' => 600]);

        $response->assertRedirect(route('public-shipment-view', $shipment->public_view_token));
        $response->assertSessionHasErrors('amount');
    }

    public function test_a_partial_amount_redirects_to_paystack(): void
    {
        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://paystack.test/checkout/abc123'],
            ]),
        ]);

        $shipment = $this->makeShipment(500);

        $response = $this->post(route('public-shipment-pay', $shipment->public_view_token), ['amount' => 100]);

        $response->assertRedirect('https://paystack.test/checkout/abc123');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/transaction/initialize')
            && $request['amount'] === 120000 // 100 USD * 12 * 100 pesewas
            && $request['currency'] === 'GHS');
    }

    public function test_the_callback_records_the_payment_once_and_updates_status(): void
    {
        Notification::fake();

        $shipment = $this->makeShipment(500);

        Http::fake([
            '*/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 120000,
                    'reference' => 'ref_abc123',
                    'channel' => 'card',
                    'paid_at' => now()->toIso8601String(),
                    'metadata' => [
                        'shipment_id' => $shipment->id,
                        'amount_usd' => 100,
                    ],
                ],
            ]),
        ]);

        $url = route('public-shipment-paid', $shipment->public_view_token).'?reference=ref_abc123';

        $this->get($url)->assertRedirect(route('public-shipment-view', $shipment->public_view_token));
        $this->get($url); // repeat — must be idempotent

        $this->assertSame(1, Payment::where('payment_ref', 'ref_abc123')->count());

        $shipment->refresh();
        $this->assertSame('partial', $shipment->payment_status);
        $this->assertEqualsWithDelta(100.0, $shipment->amount_paid, 0.01);
        $this->assertEqualsWithDelta(400.0, $shipment->outstanding_balance, 0.01);
    }
}

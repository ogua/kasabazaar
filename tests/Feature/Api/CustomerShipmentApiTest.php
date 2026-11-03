<?php

namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerShipmentApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::fake();

        $suffix = Str::random(8);
        $this->branch = Branch::create([
            'name' => 'Customer API Branch',
            'slug' => 'customer-api-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);

        $this->client = Client::create(['branch_id' => $this->branch->id, 'name' => 'Ama Mensah', 'email' => 'ama-'.$suffix.'@example.com']);

        Sanctum::actingAs(User::factory()->create(['client_id' => $this->client->id, 'status' => UserStatus::Active]));
    }

    private function shipment(string $status, array $attributes = []): Shipment
    {
        return Shipment::create(array_merge([
            'client_id' => $this->client->id,
            'branch_id' => $this->branch->id,
            'origin_branch_id' => $this->branch->id,
            'destination_branch_id' => $this->branch->id,
            'status' => $status,
            'tracking_number' => Shipment::generateTrackingNumber(),
            'shipping_cost' => 400,
            'total' => 400,
            'paid' => 100,
        ], $attributes));
    }

    public function test_status_groups_filter_the_list(): void
    {
        $this->shipment('warehouse');
        $this->shipment('in_transit');
        $this->shipment('port');
        $this->shipment('delivered');
        $this->shipment('received');

        $this->getJson('/api/v1/customer/shipments?status=in_transit')->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/customer/shipments?status=completed')->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/customer/shipments?status=awaiting')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/customer/shipments?status=pending,warehouse')->assertJsonCount(1, 'data');
    }

    public function test_the_dashboard_counts_groups_and_the_outstanding_balance(): void
    {
        $this->shipment('pickup');
        $this->shipment('shipped');
        $this->shipment('received', ['paid' => 400]);
        $this->shipment('cancelled');

        $this->getJson('/api/v1/customer/dashboard')
            ->assertOk()
            ->assertJsonPath('data.counts.total', 4)
            ->assertJsonPath('data.counts.awaiting', 1)
            ->assertJsonPath('data.counts.in_transit', 1)
            ->assertJsonPath('data.counts.completed', 1)
            ->assertJsonPath('data.counts.cancelled', 1)
            ->assertJsonPath('data.payments.outstanding_usd', 600)
            ->assertJsonCount(2, 'data.active_shipments');
    }

    public function test_the_detail_has_costs_stages_history_and_receipts(): void
    {
        $shipment = $this->shipment('port', ['vat' => 0, 'insurance' => 0]);
        $shipment->payments()->create([
            'branch_id' => $this->branch->id, 'amount' => 100, 'amount_usd' => 100, 'currency' => 'USD',
            'paying_method' => 'CASH', 'paid_on' => now(), 'payment_ref' => 'PAY-TEST', 'payment_type' => 'credit',
        ]);

        $response = $this->getJson('/api/v1/customer/shipments/'.$shipment->id)
            ->assertOk()
            ->assertJsonPath('data.status_label', 'At Harbour / Port GH')
            ->assertJsonPath('data.costs.balance', 300)
            ->assertJsonPath('data.can_rate', false)
            ->assertJsonCount(9, 'data.stages')
            ->assertJsonCount(1, 'data.payments');

        $this->assertStringContainsString('/payment-receipt/', $response->json('data.payments.0.receipt_url'));
        $this->assertStringContainsString('/shipping-invoice-download/', $response->json('data.invoice_url'));
    }

    public function test_payments_can_be_filtered_by_shipment(): void
    {
        $first = $this->shipment('pickup');
        $second = $this->shipment('pickup');

        foreach ([$first, $second] as $shipment) {
            $shipment->payments()->create([
                'branch_id' => $this->branch->id, 'amount' => 50, 'amount_usd' => 50, 'currency' => 'USD',
                'paying_method' => 'CASH', 'paid_on' => now(), 'payment_ref' => 'PAY-'.Str::random(6), 'payment_type' => 'credit',
            ]);
        }

        $this->getJson('/api/v1/customer/payments?shipment_id='.$first->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.shipment_id', $first->id);
    }
}

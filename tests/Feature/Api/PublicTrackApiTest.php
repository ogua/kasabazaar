<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Product;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicTrackApiTest extends TestCase
{
    use RefreshDatabase;

    private Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::fake();

        $suffix = Str::random(8);
        $branch = Branch::create([
            'name' => 'Michigan',
            'slug' => 'track-'.$suffix,
            'country' => 'USA',
            'state' => 'Michigan',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);

        $client = Client::create(['branch_id' => $branch->id, 'name' => 'Ama Mensah', 'email' => 'ama-'.$suffix.'@example.com', 'phone' => '+233201234567']);

        $this->shipment = Shipment::create([
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'origin_branch_id' => $branch->id,
            'destination_branch_id' => $branch->id,
            'status' => 'in_transit',
            'shipping_reference' => 'CON9-26-C1-001',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'shipped_at' => now()->subDays(3),
            'total' => 500,
        ]);

        $receiver = Receiver::create(['shipment_id' => $this->shipment->id, 'receiver_name' => 'Kwame Asante Mensah', 'receiver_phone' => '+233244000000']);
        ShipmentItem::create([
            'shipment_id' => $this->shipment->id,
            'receiver_id' => $receiver->id,
            'product_id' => Product::create(['name' => 'Barrel '.$suffix, 'category' => 'general', 'value' => 100])->id,
            'quantity' => 2,
            'item_cost' => 200,
        ]);
    }

    public function test_tracking_number_lookup_returns_the_stages_and_masked_items(): void
    {
        $this->getJson('/api/v1/shipments/track/'.$this->shipment->tracking_number)
            ->assertOk()
            ->assertJsonPath('data.status', 'in_transit')
            ->assertJsonPath('data.status_meta.step', 5)
            ->assertJsonPath('data.stages.3.done', true)
            ->assertJsonPath('data.stages.4.current', true)
            ->assertJsonPath('data.origin.place', 'Michigan')
            ->assertJsonPath('data.origin_branch', 'Michigan')
            ->assertJsonPath('data.looked_up_by', 'tracking_number')
            ->assertJsonPath('data.can_pay', true)
            ->assertJsonPath('data.items_by_receiver.0.receiver', 'Kwame M.')
            ->assertJsonMissingPath('data.items_by_receiver.0.items.0.item_cost')
            ->assertJsonCount(9, 'data.stages');
    }

    public function test_reference_lookup_shows_status_only(): void
    {
        $this->getJson('/api/v1/shipments/track/CON9-26-C1-001')
            ->assertOk()
            ->assertJsonPath('data.looked_up_by', 'reference')
            ->assertJsonPath('data.can_pay', false)
            ->assertJsonCount(0, 'data.items_by_receiver');
    }

    public function test_legacy_cleared_status_is_shown_as_the_port_stage(): void
    {
        DB::table('shipments')->where('id', $this->shipment->id)->update(['status' => 'cleared']);

        $this->getJson('/api/v1/shipments/track/'.$this->shipment->tracking_number)
            ->assertOk()
            ->assertJsonPath('data.status', 'port')
            ->assertJsonPath('data.status_meta.step', 6);
    }

    public function test_a_delivered_shipment_with_receipt_shows_as_received(): void
    {
        DB::table('shipments')->where('id', $this->shipment->id)->update(['status' => 'delivered', 'is_received' => true]);

        $this->getJson('/api/v1/shipments/track/'.$this->shipment->tracking_number)
            ->assertJsonPath('data.status', 'received')
            ->assertJsonPath('data.status_meta.step', 9);
    }

    public function test_the_alert_link_token_opens_the_same_view(): void
    {
        $this->getJson('/api/v1/shipments/view/'.$this->shipment->public_view_token)
            ->assertOk()
            ->assertJsonPath('data.tracking_number', $this->shipment->tracking_number)
            ->assertJsonPath('data.items_by_receiver.0.receiver', 'Kwame M.');

        $this->getJson('/api/v1/shipments/view/not-a-token')->assertNotFound();
    }
}

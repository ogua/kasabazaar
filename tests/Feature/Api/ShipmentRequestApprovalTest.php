<?php

namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Country;
use App\Models\Product;
use App\Models\ShipmentRequest;
use App\Models\User;
use App\Services\ShipmentRequestApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShipmentRequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private Product $product;

    private User $staff;

    private Country $ghana;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Mail::fake();
        Http::fake();

        $suffix = Str::random(8);
        $branch = Branch::create([
            'name' => 'Approval Test Branch',
            'slug' => 'approval-test-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);

        $this->client = Client::create([
            'branch_id' => $branch->id,
            'name' => 'Ama Mensah',
            'email' => 'ama-'.$suffix.'@example.com',
            'phone' => '+233201234567',
        ]);

        $this->ghana = Country::where('iso2', 'GH')->first() ?? tap(new Country, function (Country $country) {
            $country->timestamps = false;
            $country->forceFill([
                'name' => 'Ghana', 'iso2' => 'GH', 'iso3' => 'GHA', 'phone_code' => '233', 'region' => 'Test', 'subregion' => 'Test',
            ])->save();
        });

        $this->product = Product::create(['name' => 'Barrel (Large) '.$suffix, 'category' => 'general', 'value' => 120]);
        $this->staff = User::factory()->create(['status' => UserStatus::Active]);
    }

    private function request(array $items): ShipmentRequest
    {
        return ShipmentRequest::create([
            'client_id' => $this->client->id,
            'status' => 'submitted',
            'receivers' => ShipmentRequest::normaliseReceivers([[
                'name' => 'Kofi Boateng',
                'phone' => '+233244000000',
                'country' => 'Ghana',
                'city' => 'Kumasi',
                'items' => $items,
            ]]),
            'pickup_location' => 'Accra',
            'preferred_pickup_at' => now()->addDay(),
            'notes' => 'Fragile items',
        ]);
    }

    public function test_approval_builds_the_shipment_like_the_wizard(): void
    {
        $request = $this->request([['product_id' => $this->product->id, 'quantity' => 2]]);

        $shipment = app(ShipmentRequestApprovalService::class)->approve($request->load('client'), $this->staff, [
            'shipping_cost' => 300,
            'vat_percentage' => 15,
            'notes' => 'Collect on Saturday',
        ]);

        $this->assertNotNull($shipment->container_number);
        $this->assertNotNull($shipment->client_sequence);
        $this->assertSame('Fragile items', $shipment->client_note);
        $this->assertSame('Collect on Saturday', $shipment->description);
        $this->assertEquals(345, $shipment->total);

        $receiver = $shipment->receivers()->firstOrFail();
        $this->assertEquals($this->ghana->id, $receiver->country);
        $this->assertSame('Kumasi', $receiver->city);

        $item = $shipment->items()->firstOrFail();
        $this->assertNull($item->box_no);
        $this->assertEquals(240, $item->item_cost);

        $this->assertDatabaseHas('invoices', ['shipment_id' => $shipment->id, 'status' => 'unpaid']);
        $this->assertDatabaseHas('shipment_updates', ['shipment_id' => $shipment->id]);
        $this->assertSame('approved', $request->fresh()->status->value);
    }

    public function test_items_without_a_catalogue_product_must_be_priced_first(): void
    {
        $request = $this->request([['description' => 'Old sewing machine', 'quantity' => 1]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Old sewing machine');

        try {
            app(ShipmentRequestApprovalService::class)->approve($request->load('client'), $this->staff);
        } finally {
            $this->assertNull($request->fresh()->shipment_id);
            $this->assertDatabaseMissing('products', ['name' => 'Old sewing machine']);
        }
    }
}

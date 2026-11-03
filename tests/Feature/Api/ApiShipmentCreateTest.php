<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Country;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiShipmentCreateTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Client $client;

    private Product $product;

    private Country $ghana;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Mail::fake();
        Http::fake();

        $suffix = Str::random(8);
        $this->branch = Branch::create([
            'name' => 'API Create Branch',
            'slug' => 'api-create-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);

        $this->client = Client::create([
            'branch_id' => $this->branch->id,
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

        $this->product = Product::create(['name' => 'Toyota Corolla '.$suffix, 'category' => 'vehicles', 'value' => 4000]);

        $role = Role::firstOrCreate(['name' => 'api-create-'.$suffix, 'guard_name' => 'web']);
        foreach (['create_shipment', 'update_shipment', 'view_shipment'] as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        $staff = User::factory()->create(['status' => 'active', 'branch_id' => $this->branch->id]);
        $staff->assignRole($role);
        $staff->branches()->attach($this->branch);

        Sanctum::actingAs($staff);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => $this->client->id,
            'destination_branch_id' => $this->branch->id,
            'status' => 'pickup',
            'shipping_cost' => 1000,
            'discount' => 50,
            'vat_percentage' => 15,
            'insurance_accepted' => true,
            'insurance' => 20,
            'client_note' => 'Handle with care',
            'receivers' => [[
                'receiver_name' => 'Kofi Boateng',
                'receiver_phone' => '+233244000000',
                'country' => 'Ghana',
                'whatsapp_opt_in' => true,
                'items' => [[
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'item_cost' => 1000,
                    'is_vehicle' => true,
                    'vin' => '1HGCM82633A004352',
                    'vehicle_make' => 'Toyota',
                    'vehicle_model' => 'Corolla',
                    'vehicle_year' => 2018,
                ]],
            ]],
            'payments' => [[
                'paying_method' => 'cash',
                'amount_usd' => 300,
                'exchange_rate' => 15,
            ]],
        ], $overrides);
    }

    public function test_the_app_can_create_a_shipment_like_the_wizard(): void
    {
        $id = $this->withHeader('X-Branch-Id', $this->branch->id)
            ->postJson('/api/v1/shipments', $this->payload())
            ->assertCreated()
            ->json('data.id');

        $shipment = Shipment::with(['receivers', 'items', 'payments'])->findOrFail($id);

        $this->assertSame('pickup', $shipment->status->value);
        $this->assertEquals(150, $shipment->vat);
        $this->assertEquals(1120, $shipment->total);
        $this->assertSame('Handle with care', $shipment->client_note);
        $this->assertSame('new-client', $shipment->client_existence);
        $this->assertNotNull($shipment->container_number);

        $receiver = $shipment->receivers->first();
        $this->assertEquals($this->ghana->id, $receiver->country);
        $this->assertNotNull($receiver->whatsapp_opt_in_at);

        $item = $shipment->items->first();
        $this->assertTrue((bool) $item->is_vehicle);
        $this->assertSame('1HGCM82633A004352', $item->vin);

        $payment = $shipment->payments->first();
        $this->assertSame('CASH', $payment->paying_method);
        $this->assertEquals(4500, $payment->amount_ghs);

        $this->assertSame('partial', $shipment->fresh()->payment_status);
        $this->assertDatabaseHas('invoices', ['shipment_id' => $id, 'status' => 'partial']);
        $this->assertDatabaseHas('shipment_updates', ['shipment_id' => $id, 'status' => 'pickup']);
    }

    public function test_vat_is_a_percentage_up_to_100(): void
    {
        $this->withHeader('X-Branch-Id', $this->branch->id)
            ->postJson('/api/v1/shipments', $this->payload(['vat_percentage' => 150]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vat_percentage');
    }

    public function test_legacy_cleared_status_is_refused(): void
    {
        $this->withHeader('X-Branch-Id', $this->branch->id)
            ->postJson('/api/v1/shipments', $this->payload(['status' => 'cleared']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_updating_the_money_recalculates_the_total(): void
    {
        $id = $this->withHeader('X-Branch-Id', $this->branch->id)
            ->postJson('/api/v1/shipments', $this->payload(['payments' => []]))
            ->json('data.id');

        $this->putJson("/api/v1/shipments/{$id}", ['vat_percentage' => 0, 'discount' => 0, 'total' => 1])
            ->assertOk();

        $this->assertEquals(1020, Shipment::findOrFail($id)->total);
    }
}

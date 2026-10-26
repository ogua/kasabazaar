<?php

namespace Tests\Feature;

use App\Filament\Resources\ClientResource\Pages\CreateClient;
use App\Filament\Resources\ShipmentRequestResource\Pages\ListShipmentRequests;
use App\Filament\Resources\ShipmentResource;
use App\Filament\Resources\ShipmentResource\Pages\CreateShipment;
use App\Filament\Resources\ShipmentResource\Pages\CreateShipmentFromRequest;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Country;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShipmentRequest;
use App\Models\State;
use App\Models\User;
use App\Services\LocationLookup;
use App\Services\ShipmentRequestApprovalService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Help the shipment wizard gives staff: the receiver's country follows the phone's
 * dialling code, a picked product fills the item value, and a client's shipment
 * request opens in the same wizard, pre-filled, to be converted in one go.
 */
class ShipmentWizardAssistTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    private Country $ghana;

    private Country $usa;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Mail::fake();

        $this->ghana = $this->country('Ghana', 'GH', 'GHA', '233');
        $this->usa = $this->country('United States', 'US', 'USA', '1');

        $this->branch = Branch::create([
            'name' => 'Wizard Test Branch',
            'slug' => 'wizard-test-'.Str::random(8),
            'country' => 'USA',
            'state' => 'Ohio',
            'address' => '1 Test Street',
            'email' => 'wizard-'.Str::random(6).'@example.com',
            'phone' => '0200000000',
        ]);
    }

    public function test_an_international_number_finds_its_country(): void
    {
        $this->assertSame($this->ghana->id, LocationLookup::countryIdForPhone('+233244401185'));
        $this->assertSame($this->usa->id, LocationLookup::countryIdForPhone('+16145993651'));
        $this->assertNull(LocationLookup::countryIdForPhone('0244401185'));
        $this->assertNull(LocationLookup::countryIdForPhone('+999'));
        $this->assertNull(LocationLookup::countryIdForPhone(null));
    }

    public function test_a_typed_country_matches_by_name_or_code(): void
    {
        $this->assertSame($this->ghana->id, LocationLookup::countryIdForName(' ghana '));
        $this->assertSame($this->ghana->id, LocationLookup::countryIdForName('GH'));
        $this->assertSame($this->usa->id, LocationLookup::countryIdForName('usa'));
        $this->assertNull(LocationLookup::countryIdForName('Atlantis'));
    }

    public function test_a_receivers_phone_picks_the_country_and_clears_a_stale_region(): void
    {
        $this->actingAsShipmentAdmin();
        $ohio = State::create(['country_id' => $this->usa->id, 'name' => 'Ohio']);

        $page = Livewire::test(CreateShipment::class);
        $receiver = $this->firstKey($page->get('data.receivers'));

        $page->set("data.receivers.{$receiver}.country", $this->usa->id)
            ->set("data.receivers.{$receiver}.state_region", $ohio->id)
            ->set("data.receivers.{$receiver}.receiver_phone", '+233244401185')
            ->assertSet("data.receivers.{$receiver}.country", $this->ghana->id)
            ->assertSet("data.receivers.{$receiver}.state_region", null);
    }

    public function test_a_local_number_leaves_the_chosen_country_alone(): void
    {
        $this->actingAsShipmentAdmin();

        $page = Livewire::test(CreateShipment::class);
        $receiver = $this->firstKey($page->get('data.receivers'));

        $page->set("data.receivers.{$receiver}.country", $this->usa->id)
            ->set("data.receivers.{$receiver}.receiver_phone", '0244401185')
            ->assertSet("data.receivers.{$receiver}.country", $this->usa->id);
    }

    public function test_a_senders_phone_picks_their_country_on_the_client_form(): void
    {
        $this->actingAsShipmentAdmin(['view_any_client', 'create_client']);

        Livewire::test(CreateClient::class)
            ->set('data.phone', '+16145993651')
            ->assertSet('data.country', $this->usa->id);
    }

    public function test_picking_a_product_fills_the_item_value_and_quantity_rescales_it(): void
    {
        $this->actingAsShipmentAdmin();
        $box = Product::create(['name' => 'Large Box', 'category' => 'general', 'value' => 60]);

        $page = Livewire::test(CreateShipment::class);
        $receiver = $this->firstKey($page->get('data.receivers'));
        $item = $this->firstKey($page->get("data.receivers.{$receiver}.items"));
        $path = "data.receivers.{$receiver}.items.{$item}";

        $page->set("{$path}.product_id", $box->id)
            ->assertSet("{$path}.item_cost", 60.0)
            ->set("{$path}.quantity", 3)
            ->assertSet("{$path}.item_cost", 180.0);
    }

    public function test_a_value_staff_typed_is_kept_when_the_quantity_changes(): void
    {
        $this->actingAsShipmentAdmin();
        $box = Product::create(['name' => 'Large Box', 'category' => 'general', 'value' => 60]);

        $page = Livewire::test(CreateShipment::class);
        $receiver = $this->firstKey($page->get('data.receivers'));
        $item = $this->firstKey($page->get("data.receivers.{$receiver}.items"));
        $path = "data.receivers.{$receiver}.items.{$item}";

        $page->set("{$path}.product_id", $box->id)
            ->set("{$path}.item_cost", 45)
            ->set("{$path}.quantity", 2)
            ->assertSet("{$path}.item_cost", 45);
    }

    public function test_a_request_opens_in_the_wizard_pre_filled(): void
    {
        $this->actingAsShipmentAdmin();
        $request = $this->makeRequest();

        $page = Livewire::test(CreateShipmentFromRequest::class, ['shipmentRequest' => $request->id]);

        $page->assertSet('data.client_id', $request->client_id)
            ->assertSet('data.client_note', 'Please call before pickup');

        $receivers = array_values($page->get('data.receivers'));
        $this->assertCount(2, $receivers);

        $this->assertSame('Yealie Sakilla', $receivers[0]['receiver_name']);
        $this->assertSame($this->ghana->id, $receivers[0]['country']);
        $this->assertSame('12 Ring Road, Accra', $receivers[0]['address']);
        $items = array_values($receivers[0]['items']);
        $this->assertCount(2, $items, 'Both requested items for the first receiver must be in the wizard.');
        $this->assertSame(2, $items[0]['quantity']);
        $this->assertEquals(14.0, $items[1]['item_cost']);
        $this->assertEquals(120.0, $items[0]['item_cost']);

        // "United States" was not typed, but the +1 number gives it away.
        $this->assertSame($this->usa->id, $receivers[1]['country']);
    }

    public function test_an_item_the_client_typed_shows_its_name_and_keeps_its_value_once_added_with_plus(): void
    {
        $this->actingAsShipmentAdmin();
        $request = $this->makeRequest();
        $request->update(['receivers' => ShipmentRequest::normaliseReceivers([[
            'name' => 'Ahmed Ahi',
            'phone' => '+233244401185',
            'items' => [['description' => 'Shoe', 'quantity' => 2, 'estimated_value' => 25]],
        ]])]);

        $page = Livewire::test(CreateShipmentFromRequest::class, ['shipmentRequest' => $request->id])
            ->assertSee('Client asked for &quot;Shoe&quot;', false);

        $receiver = $this->firstKey($page->get('data.receivers'));
        $item = $this->firstKey($page->get("data.receivers.{$receiver}.items"));
        $path = "receivers.{$receiver}.items.{$item}";

        $this->assertEquals(50.0, $page->get("data.{$path}.item_cost"));

        $page->mountFormComponentAction("{$path}.product_id", 'createOption')
            ->assertFormComponentActionDataSet(['name' => 'Shoe'])
            ->callMountedFormComponentAction()
            ->assertHasNoFormComponentActionErrors();

        $shoe = Product::where('name', 'Shoe')->latest()->firstOrFail();
        $page->assertSet("data.{$path}.product_id", $shoe->id)
            ->assertSet("data.{$path}.item_cost", 50.0)
            ->assertDontSee('Client asked for &quot;Shoe&quot;', false);
    }

    public function test_every_new_product_item_of_a_receiver_reaches_the_wizard(): void
    {
        $this->actingAsShipmentAdmin();
        $request = $this->makeRequest();
        // Exactly as an older pickup-link request was stored: no product_id key, text numbers.
        $request->update(['receivers' => [[
            'name' => 'Ahmed Ahi',
            'phone' => '0545819229',
            'country' => 'Ghana',
            'state_region' => null,
            'city' => 'Accra',
            'address' => null,
            'items' => [
                ['description' => 'Shoe', 'quantity' => '1', 'estimated_value' => '0'],
                ['description' => 'Bicycle', 'quantity' => '2', 'estimated_value' => '0'],
            ],
        ]]]);

        $page = Livewire::test(CreateShipmentFromRequest::class, ['shipmentRequest' => $request->id]);
        $receiver = $this->firstKey($page->get('data.receivers'));

        $this->assertCount(2, $page->get("data.receivers.{$receiver}.items"));

        // And still after a round trip to the server, as when staff click Next.
        $page->call('$refresh');
        $this->assertCount(2, $page->get("data.receivers.{$receiver}.items"));
        $page->assertSee('Client asked for &quot;Shoe&quot;', false)
            ->assertSee('Client asked for &quot;Bicycle&quot;', false);
    }

    public function test_converting_a_request_creates_the_shipment_and_approves_the_request(): void
    {
        $admin = $this->actingAsShipmentAdmin();
        $request = $this->makeRequest();

        Livewire::test(CreateShipmentFromRequest::class, ['shipmentRequest' => $request->id])
            ->set('data.origin_branch_id', 'Ohio')
            ->set('data.shipped_at', now()->addWeek()->toDateTimeString())
            ->call('create')
            ->assertHasNoFormErrors();

        $request->refresh();
        $shipment = Shipment::with('receivers.items', 'invoice')->findOrFail($request->shipment_id);

        $this->assertSame('approved', $request->status->value);
        $this->assertSame($admin->id, $request->reviewed_by);
        $this->assertSame($request->client_id, $shipment->client_id);
        $this->assertSame($this->branch->id, $shipment->branch_id);
        $this->assertCount(2, $shipment->receivers);
        $this->assertSame('12 Ring Road, Accra', $shipment->receivers->firstWhere('receiver_name', 'Yealie Sakilla')->address);
        $this->assertCount(3, $shipment->receivers->flatMap->items);
        $this->assertEquals(120 + 14 + 14, (float) $shipment->receivers->flatMap->items->sum('item_cost'));
        $this->assertEquals(148, (float) $shipment->shipping_cost);
        $this->assertNotNull($shipment->invoice);
    }

    public function test_a_request_converted_meanwhile_is_not_converted_twice(): void
    {
        $this->actingAsShipmentAdmin();
        $request = $this->makeRequest();

        $page = Livewire::test(CreateShipmentFromRequest::class, ['shipmentRequest' => $request->id])
            ->set('data.origin_branch_id', 'Ohio')
            ->set('data.shipped_at', now()->addWeek()->toDateTimeString());

        $request->update(['status' => 'approved']);

        $before = Shipment::count();
        $page->call('create');

        $this->assertSame($before, Shipment::count());
    }

    public function test_an_approved_request_cannot_be_opened_for_conversion(): void
    {
        $this->actingAsShipmentAdmin();
        $request = $this->makeRequest(['status' => 'rejected']);

        Livewire::test(CreateShipmentFromRequest::class, ['shipmentRequest' => $request->id])
            ->assertRedirect();
    }

    public function test_the_request_list_offers_conversion_in_the_wizard(): void
    {
        $this->actingAsShipmentAdmin(['view_any_shipment::request', 'view_shipment::request']);
        $request = $this->makeRequest();

        Livewire::test(ListShipmentRequests::class)
            ->assertTableActionVisible('convert', $request)
            ->assertTableActionHasUrl('convert', ShipmentResource::getUrl('from-request', ['shipmentRequest' => $request->id]), $request);
    }

    public function test_quick_approval_stores_line_values_not_unit_values(): void
    {
        $admin = $this->actingAsShipmentAdmin();
        $request = $this->makeRequest();

        $shipment = app(ShipmentRequestApprovalService::class)->approve($request, $admin, ['branch_id' => $this->branch->id]);

        $values = $shipment->receivers->flatMap->items->pluck('item_cost')->map(fn ($value) => (float) $value)->sort()->values()->all();
        $this->assertSame([14.0, 14.0, 120.0], $values);
    }

    private function makeRequest(array $overrides = []): ShipmentRequest
    {
        $client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Samuel Samura',
            'email' => 'samuel-'.Str::random(6).'@kasarose-mail.com',
            'phone' => '+16145993651',
        ]);

        $box = Product::create(['name' => 'Box', 'category' => 'general', 'value' => 60]);
        $rice = Product::create(['name' => 'Jasmine rice (50lbs)', 'category' => 'general', 'value' => 14]);

        return ShipmentRequest::create(array_merge([
            'client_id' => $client->id,
            'status' => 'submitted',
            'pickup_location' => '6554 Marissa St, Canal Winchester',
            'preferred_pickup_at' => now()->addDay(),
            'notes' => 'Please call before pickup',
            'receivers' => ShipmentRequest::normaliseReceivers([
                [
                    'name' => 'Yealie Sakilla',
                    'phone' => '0264141541',
                    'country' => 'Ghana',
                    'address' => '12 Ring Road, Accra',
                    'items' => [
                        ['product_id' => $box->id, 'quantity' => 2],
                        ['product_id' => $rice->id, 'quantity' => 1],
                    ],
                ],
                [
                    'name' => 'Edward Austin',
                    'phone' => '+16145550000',
                    'items' => [['product_id' => $rice->id, 'quantity' => 1]],
                ],
            ]),
        ], $overrides));
    }

    private function country(string $name, string $iso2, string $iso3, string $phoneCode): Country
    {
        $existing = Country::where('iso2', $iso2)->first();

        if ($existing) {
            return $existing;
        }

        $country = new Country;
        $country->timestamps = false;
        $country->forceFill([
            'name' => $name,
            'iso2' => $iso2,
            'iso3' => $iso3,
            'phone_code' => $phoneCode,
            'region' => 'Test',
            'subregion' => 'Test',
        ])->save();

        return $country;
    }

    /**
     * @param  array<int, string>  $extraPermissions
     */
    private function actingAsShipmentAdmin(array $extraPermissions = []): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        foreach (['view_any_shipment', 'view_shipment', 'create_shipment', 'update_shipment', ...$extraPermissions] as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole($role);
        $admin->branches()->attach($this->branch);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($this->branch);

        return $admin;
    }

    private function firstKey(?array $items): string
    {
        $this->assertNotEmpty($items);

        return (string) array_key_first($items);
    }
}

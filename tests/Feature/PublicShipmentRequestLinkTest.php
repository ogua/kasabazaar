<?php

namespace Tests\Feature;

use App\Filament\Resources\ShipmentRequestLinkResource\Pages\ListShipmentRequestLinks;
use App\Filament\Resources\ShipmentRequestResource\Pages\EditShipmentRequest;
use App\Filament\Resources\ShipmentRequestResource\Pages\ListShipmentRequests;
use App\Filament\Resources\ShipmentRequestResource\Pages\ViewShipmentRequest;
use App\Models\Branch;
use App\Models\Client;
use App\Models\PickupSchedule;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShipmentRequest;
use App\Models\ShipmentRequestLink;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\PickupRequestSubmitted;
use App\Service\SystemSetting;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublicShipmentRequestLinkTest extends TestCase
{
    use DatabaseTransactions;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.admin_email' => null, 'app.admin_phone' => null]);

        $this->product = Product::create([
            'name' => 'Barrel (Large) '.Str::random(4),
            'category' => 'general',
            'value' => 120,
        ]);
    }

    private function makeClient(string $phone = '+233201234567'): Client
    {
        return Client::create([
            'branch_id' => (string) Str::uuid(),
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'phone' => $phone,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeLink(array $attributes = []): ShipmentRequestLink
    {
        return ShipmentRequestLink::create(array_merge([
            'token' => ShipmentRequestLink::generateToken(),
            'branch_id' => (string) Str::uuid(),
            'max_submissions' => 1,
            'expires_at' => now()->addDays(7),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'sender_name' => 'Kofi Boateng',
            'sender_phone' => '+233 24 555 0101',
            'sender_email' => 'kofi@example.com',
            'pickup_location' => '12 Ring Road, Accra',
            'preferred_pickup_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'notes' => 'Call on arrival',
            'receivers' => [
                [
                    'name' => 'Yaw Boateng',
                    'phone' => '+17735550100',
                    'country' => 'United States',
                    'city' => 'Chicago',
                    'address' => '1 Main St',
                    'items' => [
                        ['product_id' => $this->product->id, 'details' => 'Clothes and shoes', 'quantity' => 2, 'estimated_value' => 9999],
                    ],
                ],
            ],
        ], $overrides);
    }

    public function test_generated_tokens_are_unique(): void
    {
        $tokens = collect(range(1, 20))->map(fn () => $this->makeLink()->token);

        $this->assertCount(20, $tokens->unique());
        $this->assertSame(40, strlen($tokens->first()));
    }

    public function test_a_valid_link_shows_the_form(): void
    {
        $link = $this->makeLink();

        $this->get(route('public-pickup-request', $link->token))
            ->assertOk()
            ->assertSee('Pickup Request')
            ->assertSee('Your Details');
    }

    public function test_a_client_bound_link_greets_the_client_and_hides_sender_fields(): void
    {
        $client = $this->makeClient();
        $link = $this->makeLink(['client_id' => $client->id]);

        $this->get(route('public-pickup-request', $link->token))
            ->assertOk()
            ->assertSee('Hello, Ama Mensah!')
            ->assertDontSee('Your Details');
    }

    public function test_an_unknown_token_is_not_found(): void
    {
        $this->get(route('public-pickup-request', 'not-a-real-token'))
            ->assertNotFound()
            ->assertSee('Link Not Found');
    }

    public function test_an_open_link_creates_a_client_and_a_shipment_request(): void
    {
        $branch = Branch::create([
            'name' => 'Pickup Link Accra',
            'slug' => 'pickup-link-accra-'.Str::random(6),
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'accra-'.Str::random(6).'@example.com',
            'phone' => '0200000000',
        ]);
        $link = $this->makeLink(['branch_id' => $branch->id]);

        $this->post(route('public-pickup-request.store', $link->token), $this->payload())
            ->assertRedirect(route('public-pickup-request.submitted', $link->token));

        $client = Client::where('phone', '+233245550101')->firstOrFail();
        $this->assertSame('Kofi Boateng', $client->name);
        $this->assertSame($link->branch_id, $client->branch_id);

        $request = ShipmentRequest::where('shipment_request_link_id', $link->id)->firstOrFail();
        $this->assertSame($client->id, $request->client_id);
        $this->assertSame('public_link', $request->source);
        $this->assertSame('submitted', $request->status->value);
        $this->assertSame($this->product->name, $request->receivers[0]['items'][0]['description']);
        $this->assertSame('Clothes and shoes', $request->receivers[0]['items'][0]['details']);

        $this->assertTrue(PickupSchedule::where('client_id', $client->id)->exists());

        $link->refresh();
        $this->assertSame(1, $link->submissions_count);
        $this->assertNotNull($link->last_submitted_at);
    }

    public function test_an_open_link_reuses_an_existing_client_with_the_same_phone(): void
    {
        $existing = $this->makeClient('+233245550101');
        $link = $this->makeLink();

        $this->post(route('public-pickup-request.store', $link->token), $this->payload());

        $this->assertSame(1, Client::where('phone', '+233245550101')->count());
        $this->assertSame($existing->id, ShipmentRequest::where('shipment_request_link_id', $link->id)->value('client_id'));
    }

    public function test_a_client_bound_link_files_the_request_under_that_client_without_sender_details(): void
    {
        $client = $this->makeClient();
        $link = $this->makeLink(['client_id' => $client->id]);

        $this->post(route('public-pickup-request.store', $link->token), $this->payload([
            'sender_name' => null,
            'sender_phone' => null,
            'sender_email' => null,
        ]))->assertRedirect(route('public-pickup-request.submitted', $link->token));

        $this->assertSame($client->id, ShipmentRequest::where('shipment_request_link_id', $link->id)->value('client_id'));
    }

    public function test_an_open_link_requires_sender_details(): void
    {
        $link = $this->makeLink();

        $this->post(route('public-pickup-request.store', $link->token), $this->payload([
            'sender_name' => '',
            'sender_phone' => '',
        ]))->assertSessionHasErrors(['sender_name', 'sender_phone']);

        $this->assertSame(0, $link->fresh()->submissions_count);
    }

    public function test_receivers_items_and_a_future_pickup_date_are_required(): void
    {
        $link = $this->makeLink();

        $this->post(route('public-pickup-request.store', $link->token), $this->payload([
            'receivers' => [['name' => 'Yaw', 'phone' => '123', 'items' => []]],
            'preferred_pickup_at' => now()->subDay()->format('Y-m-d\TH:i'),
        ]))->assertSessionHasErrors(['receivers.0.items', 'preferred_pickup_at']);
    }

    public function test_a_single_use_link_cannot_be_used_twice(): void
    {
        $link = $this->makeLink();

        $this->post(route('public-pickup-request.store', $link->token), $this->payload());

        $this->get(route('public-pickup-request', $link->token))
            ->assertStatus(410)
            ->assertSee('Already Been Used');

        $this->post(route('public-pickup-request.store', $link->token), $this->payload())
            ->assertStatus(410);

        $this->assertSame(1, ShipmentRequest::where('shipment_request_link_id', $link->id)->count());
    }

    public function test_a_multi_use_link_accepts_several_submissions(): void
    {
        $link = $this->makeLink(['max_submissions' => null]);

        $this->post(route('public-pickup-request.store', $link->token), $this->payload());
        $this->post(route('public-pickup-request.store', $link->token), $this->payload(['sender_phone' => '0201112222']));

        $this->assertSame(2, ShipmentRequest::where('shipment_request_link_id', $link->id)->count());
        $this->get(route('public-pickup-request.submitted', $link->token))->assertSee('Submit Another Request');
    }

    public function test_expired_and_revoked_links_are_refused(): void
    {
        $expired = $this->makeLink(['expires_at' => now()->subMinute()]);
        $revoked = $this->makeLink(['revoked_at' => now()]);

        $this->get(route('public-pickup-request', $expired->token))->assertStatus(410)->assertSee('Expired');
        $this->post(route('public-pickup-request.store', $revoked->token), $this->payload())
            ->assertStatus(410)
            ->assertSee('No Longer Active');

        $this->assertSame(0, ShipmentRequest::whereIn('shipment_request_link_id', [$expired->id, $revoked->id])->count());
    }

    public function test_the_customer_app_still_submits_requests_through_the_api(): void
    {
        $client = $this->makeClient();
        $user = User::factory()->create(['client_id' => $client->id, 'status' => 'active']);
        Sanctum::actingAs($user);

        $payload = $this->payload();
        unset($payload['sender_name'], $payload['sender_phone'], $payload['sender_email']);
        $payload['receivers'][0]['items'][0]['description'] = 'Box of clothes';

        $this->postJson('/api/v1/customer/shipment-requests', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted');

        $this->assertSame('app', ShipmentRequest::where('client_id', $client->id)->value('source'));
    }

    public function test_the_product_search_returns_matching_products_with_their_values(): void
    {
        $link = $this->makeLink();

        $this->get(route('public-pickup-request', $link->token))
            ->assertOk()
            ->assertSee($link->token.'\/products', false)
            ->assertSee('+ New product');

        $this->getJson(route('public-pickup-request.products', $link->token).'?q='.urlencode($this->product->name))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->product->id,
                'name' => $this->product->name,
                'value' => '120.00',
            ]);
    }

    public function test_the_product_search_is_closed_for_unusable_links(): void
    {
        $link = $this->makeLink(['revoked_at' => now()]);

        $this->getJson(route('public-pickup-request.products', $link->token))->assertNotFound();
    }

    public function test_a_client_bound_link_offers_the_clients_previous_receivers(): void
    {
        $client = $this->makeClient();
        $this->makeShipmentRequest(['client_id' => $client->id]);
        $link = $this->makeLink(['client_id' => $client->id]);

        $this->get(route('public-pickup-request', $link->token))
            ->assertOk()
            ->assertSee("Send to someone you've shipped to before?", false)
            ->assertSee('Yaw Boateng (+17735550100)', false);
    }

    public function test_an_open_link_never_shows_previous_receivers(): void
    {
        $link = $this->makeLink();

        $this->get(route('public-pickup-request', $link->token))
            ->assertOk()
            ->assertDontSee('shipped to before', false);
    }

    public function test_admins_are_alerted_by_email_and_sms_when_a_pickup_request_arrives(): void
    {
        NotificationFacade::fake();
        SystemSetting::set('admin_alert_emails', 'ops@example.com, manager@example.com', 'sms');
        SystemSetting::set('admin_alert_phones', '+233201112222', 'sms');
        $link = $this->makeLink();

        $this->post(route('public-pickup-request.store', $link->token), $this->payload());

        NotificationFacade::assertSentOnDemand(
            PickupRequestSubmitted::class,
            fn ($notification, array $channels, object $notifiable): bool => in_array('mail', $channels, true)
                && $notifiable->routes['mail'] === ['ops@example.com', 'manager@example.com']
        );
        NotificationFacade::assertSentOnDemand(
            PickupRequestSubmitted::class,
            fn ($notification, array $channels, object $notifiable): bool => in_array(SmsChannel::class, $channels, true)
                && $notifiable->routes['sms'] === '+233201112222'
                && str_contains($notification->toSms($notifiable), 'New pickup request from Kofi Boateng')
        );
    }

    public function test_the_admin_alert_email_links_to_the_request(): void
    {
        $request = $this->makeShipmentRequest();
        $notification = new PickupRequestSubmitted($request);

        $html = (string) $notification->toMail(new \Illuminate\Notifications\AnonymousNotifiable)->render();

        $this->assertStringContainsString('New Shipment Request', $html);
        $this->assertStringContainsString($request->id, $notification->reviewUrl());
    }

    public function test_item_values_come_from_the_product_not_the_client(): void
    {
        $link = $this->makeLink();

        $this->post(route('public-pickup-request.store', $link->token), $this->payload());

        $item = ShipmentRequest::where('shipment_request_link_id', $link->id)->firstOrFail()->receivers[0]['items'][0];
        $this->assertSame($this->product->id, $item['product_id']);
        $this->assertEquals(120, $item['estimated_value']);
    }

    public function test_a_client_can_request_a_product_that_is_not_listed(): void
    {
        $link = $this->makeLink();
        $payload = $this->payload();
        $payload['receivers'][0]['items'] = [
            ['description' => 'Sewing machine', 'details' => 'Singer, boxed', 'quantity' => 1, 'estimated_value' => 500],
        ];

        $this->post(route('public-pickup-request.store', $link->token), $payload)->assertRedirect();

        $item = ShipmentRequest::where('shipment_request_link_id', $link->id)->firstOrFail()->receivers[0]['items'][0];
        $this->assertNull($item['product_id']);
        $this->assertSame('Sewing machine', $item['description']);
        $this->assertNull($item['estimated_value']);
        $this->assertFalse(Product::where('name', 'Sewing machine')->exists());
    }

    public function test_every_item_needs_a_product_or_a_new_product_name(): void
    {
        $link = $this->makeLink();
        $payload = $this->payload();
        $payload['receivers'][0]['items'] = [['details' => 'Something', 'quantity' => 1]];

        $this->post(route('public-pickup-request.store', $link->token), $payload)
            ->assertSessionHasErrors(['receivers.0.items.0.product_id', 'receivers.0.items.0.description']);
    }

    public function test_staff_can_edit_a_request_and_values_are_refreshed_from_products(): void
    {
        $this->actingAsStaff(['view_any_shipment::request', 'view_shipment::request', 'update_shipment::request']);
        $request = $this->makeShipmentRequest();
        $other = Product::create(['name' => 'Fridge '.Str::random(4), 'category' => 'appliances', 'value' => 300]);

        $receivers = $request->receivers;
        $receivers[0]['items'][0] = ['product_id' => $other->id, 'description' => 'stale', 'details' => 'Double door', 'quantity' => 3, 'estimated_value' => 1];

        $undoRepeaterFake = Repeater::fake();

        Livewire::test(EditShipmentRequest::class, ['record' => $request->getRouteKey()])
            ->fillForm([
                'pickup_location' => '5 Spintex Road, Accra',
                'receivers' => $receivers,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();
        $request->refresh();
        $item = $request->receivers[0]['items'][0];
        $this->assertSame('5 Spintex Road, Accra', $request->pickup_location);
        $this->assertSame($other->id, $item['product_id']);
        $this->assertSame($other->name, $item['description']);
        $this->assertEquals(300, $item['estimated_value']);
        $this->assertSame(3, $item['quantity']);
        $this->assertSame('Double door', $item['details']);
    }

    public function test_the_view_page_lists_each_receivers_items(): void
    {
        $this->actingAsStaff(['view_any_shipment::request', 'view_shipment::request', 'update_shipment::request']);
        $request = $this->makeShipmentRequest();

        Livewire::test(ViewShipmentRequest::class, ['record' => $request->getRouteKey()])
            ->assertSuccessful()
            ->assertSee($this->product->name)
            ->assertSee('Clothes and shoes');
    }

    public function test_approved_requests_cannot_be_edited(): void
    {
        $this->actingAsStaff(['view_any_shipment::request', 'view_shipment::request', 'update_shipment::request']);
        $request = $this->makeShipmentRequest(['status' => 'approved']);

        Livewire::test(EditShipmentRequest::class, ['record' => $request->getRouteKey()])
            ->assertRedirect();
    }

    public function test_tracking_numbers_link_to_the_public_tracking_page(): void
    {
        $shipment = new Shipment(['tracking_number' => 'KBZ2854072']);

        $this->assertSame('https://kasarose.com/tracking?ref=KBZ2854072', $shipment->publicTrackingUrl());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeShipmentRequest(array $attributes = []): ShipmentRequest
    {
        return ShipmentRequest::create(array_merge([
            'client_id' => $this->makeClient()->id,
            'status' => 'submitted',
            'receivers' => ShipmentRequest::normaliseReceivers($this->payload()['receivers']),
            'pickup_location' => '12 Ring Road, Accra',
            'preferred_pickup_at' => now()->addDays(2),
        ], $attributes));
    }

    public function test_staff_can_create_a_pickup_link_from_the_shipment_requests_page(): void
    {
        $this->actingAsStaff(['view_any_shipment::request', 'create_shipment::request']);
        $client = $this->makeClient();

        Livewire::test(ListShipmentRequests::class)
            ->callAction('createPickupLink', [
                'client_id' => $client->id,
                'label' => 'Facebook enquiry',
                'expires_in_days' => 3,
                'max_submissions' => 1,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Pickup link created');

        $link = ShipmentRequestLink::where('label', 'Facebook enquiry')->firstOrFail();
        $this->assertSame($client->id, $link->client_id);
        $this->assertSame(Filament::getTenant()->getKey(), $link->branch_id);
        $this->assertTrue($link->expires_at->between(now()->addDays(3)->subMinute(), now()->addDays(3)->addMinute()));
    }

    public function test_links_can_expire_in_14_days(): void
    {
        $this->actingAsStaff(['view_any_shipment::request', 'create_shipment::request']);

        Livewire::test(ListShipmentRequests::class)
            ->callAction('createPickupLink', [
                'label' => 'Fourteen day link',
                'expires_in_days' => 14,
                'max_submissions' => 1,
            ])
            ->assertHasNoActionErrors();

        $link = ShipmentRequestLink::where('label', 'Fourteen day link')->firstOrFail();
        $this->assertNull($link->client_id);
        $this->assertTrue($link->expires_at->between(now()->addDays(14)->subMinute(), now()->addDays(14)->addMinute()));
    }

    public function test_approval_adds_vat_and_insurance_to_the_shipment_total(): void
    {
        NotificationFacade::fake();
        \Illuminate\Support\Facades\Mail::fake();
        $staff = $this->actingAsStaff(['view_any_shipment::request']);
        $client = $this->makeClient();
        $client->update(['branch_id' => Filament::getTenant()->getKey()]);
        $request = $this->makeShipmentRequest(['client_id' => $client->id]);

        $shipment = (new \App\Services\ShipmentRequestApprovalService)->approve($request->load('client'), $staff, [
            'shipping_cost' => 200,
            'vat_percentage' => 15,
            'insurance_accepted' => true,
            'insurance' => 25,
        ]);

        $this->assertEquals(200, $shipment->shipping_cost);
        $this->assertEquals(30, $shipment->vat);
        $this->assertTrue((bool) $shipment->insurance_accepted);
        $this->assertEquals(25, $shipment->insurance);
        $this->assertEquals(255, $shipment->total);
    }

    public function test_the_approve_form_prefills_the_items_total(): void
    {
        $request = $this->makeShipmentRequest();

        $this->assertEquals(240, $request->itemsTotal());
    }

    public function test_staff_can_revoke_a_link(): void
    {
        $this->actingAsStaff(['view_any_shipment::request', 'create_shipment::request']);
        $link = $this->makeLink();

        Livewire::test(ListShipmentRequestLinks::class)
            ->callTableAction('revoke', $link);

        $this->assertFalse($link->fresh()->isUsable());
    }

    public function test_staff_without_create_permission_cannot_create_links(): void
    {
        $this->actingAsStaff(['view_any_shipment::request']);

        Livewire::test(ListShipmentRequests::class)
            ->assertActionHidden('createPickupLink');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actingAsStaff(array $permissions): User
    {
        $role = Role::firstOrCreate(['name' => 'pickup-link-test', 'guard_name' => 'web']);
        $role->syncPermissions(array_map(
            fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']),
            $permissions
        ));

        $staff = User::factory()->create(['status' => 'active']);
        $staff->assignRole($role);

        $branch = Branch::create([
            'name' => 'Pickup Link Branch',
            'slug' => 'pickup-link-'.$staff->id,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$staff->id.'@example.com',
            'phone' => '0200000000',
        ]);
        $staff->branches()->attach($branch);

        $this->actingAs($staff);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($branch);

        return $staff;
    }
}

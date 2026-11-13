<?php

use App\Livewire\Storefront\Account\OrderDetail;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Storefront\Membership;
use App\Models\User;
use App\Services\Kasabazaar\CheckoutApi;
use App\Support\ProductCondition;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.kasabazaar.base_url' => 'https://kasabazaar.test/api/v1']);
});

// ─── Item condition ───────────────────────────────────────────────────────────

it('offers exactly the four conditions and falls back to new for unknown keys', function () {
    expect(array_keys(ProductCondition::options()))->toBe(['new', 'factory_sealed', 'refurbished', 'used']);
    expect(ProductCondition::for(null)['key'])->toBe('new');
    expect(ProductCondition::for('mint')['key'])->toBe('new');
    expect(ProductCondition::for('used')['label'])->toBe('Used');
});

it('renders the condition chip with its explanatory popover text', function () {
    $html = Blade::render('<x-storefront.condition-badge condition="refurbished" />');

    expect($html)
        ->toContain('Refurbished')
        ->toContain('role="tooltip"')
        ->toContain(ProductCondition::for('refurbished')['description'])
        // The popover explains every condition, not just this item's.
        ->toContain(e(ProductCondition::for('factory_sealed')['description']))
        ->toContain(ProductCondition::for('used')['description'])
        ->toContain(ProductCondition::for('new')['description']);
});

// ─── Delivery method ──────────────────────────────────────────────────────────

function fakeCheckoutBackend(array $methods): void
{
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/cart' => Http::response(['success' => true, 'data' => [
            'items' => [
                ['id' => 'i-1', 'quantity' => 1, 'price_ghs' => 100, 'product' => ['name' => 'Kettle']],
                ['id' => 'i-2', 'quantity' => 1, 'price_ghs' => 200, 'product' => ['name' => 'Laptop']],
            ],
            'subtotal_ghs' => 300,
        ]]),
        'kasabazaar.test/api/v1/marketplace/addresses' => Http::response(['success' => true, 'data' => [
            ['id' => 'addr-1', 'full_name' => 'Ama', 'phone' => '0200000000', 'city' => 'Accra', 'region' => 'Greater Accra', 'is_default' => true],
        ]]),
        'kasabazaar.test/api/v1/marketplace/delivery-options*' => Http::response(['success' => true, 'data' => ['total_items' => 2, 'methods' => $methods]]),
        'kasabazaar.test/api/v1/marketplace/checkout' => Http::response(['success' => true, 'data' => ['id' => 'grp-1']], 201),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);
}

function deliveryMethods(): array
{
    return [
        ['key' => 'delivery', 'label' => 'Delivery', 'items_covered' => 1, 'selectable' => true, 'fee_mode' => 'quoted', 'description' => 'd'],
        ['key' => 'pickup', 'label' => 'Pickup', 'items_covered' => 1, 'selectable' => true, 'fee_mode' => 'free', 'description' => 'p'],
        ['key' => 'kasarose_shipping', 'label' => 'KASAROSE Shipping', 'items_covered' => 2, 'weight_kg' => 10.0, 'selectable' => true, 'fee_mode' => 'quoted', 'description' => 'Usually 14 to 28 days.'],
    ];
}

it('shows the delivery modes without a price for the ones our team quotes', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    fakeCheckoutBackend(deliveryMethods());

    Livewire::test(Checkout::class)
        ->assertSee('Pickup and delivery options')
        ->assertSee('Available for 1 of 2 items')
        ->assertSee('Available for all items')
        ->assertSee('KASAROSE Shipping')
        ->assertSee('Cost confirmed after you order')
        ->assertSee('Quoted after you order')
        // Nothing is added to the total for delivery: the shipping team sets it later.
        ->assertSee('Total before delivery')
        ->assertSee('GHS 300.00')
        ->set('deliveryMethod', 'pickup')
        ->assertSee('Free');
});

it('sends the chosen delivery method with the order', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    fakeCheckoutBackend(deliveryMethods());

    Livewire::test(Checkout::class)->set('deliveryMethod', 'pickup')->set('pickupStationId', 'st-1')->call('placeOrder');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/marketplace/checkout')
        && $request['delivery_method'] === 'pickup');
});

it('sends KASAROSE shipping when the shopper chooses it', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    fakeCheckoutBackend(deliveryMethods());

    Livewire::test(Checkout::class)->set('deliveryMethod', 'kasarose_shipping')->call('placeOrder');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/marketplace/checkout')
        && $request['delivery_method'] === 'kasarose_shipping');
});

it('passes the delivery method through the checkout API call', function () {
    Http::fake(['kasabazaar.test/*' => Http::response(['success' => true, 'data' => ['id' => 'grp-1']], 201)]);

    app(CheckoutApi::class)->checkout('addr-1', '', false, 'pickup');

    Http::assertSent(fn (Request $request) => $request['delivery_method'] === 'pickup');
});

// ─── Membership ───────────────────────────────────────────────────────────────

function fakePlans(): array
{
    return [
        ['id' => 'plan-basic', 'tier' => 'basic', 'name' => 'Basic', 'description' => null, 'price_ghs' => 50.0, 'duration_months' => 12, 'product_discount_percent' => 2.0, 'exclusive_auctions' => false, 'perks' => ['2% off marketplace products']],
        ['id' => 'plan-exec', 'tier' => 'executive', 'name' => 'Executive', 'description' => 'Everything.', 'price_ghs' => 600.0, 'duration_months' => 12, 'product_discount_percent' => 12.0, 'exclusive_auctions' => true, 'perks' => ['Every exclusive member auction']],
    ];
}

it('lists the membership plans to guests', function () {
    Http::fake(['kasabazaar.test/api/v1/marketplace/membership/plans' => Http::response(['success' => true, 'data' => fakePlans()])]);

    $this->get(route('storefront.membership'))
        ->assertOk()
        ->assertSee('Executive')
        ->assertSee('GHS 600')
        ->assertSee('Join Basic');
});

it('sends a guest to log in before paying membership dues', function () {
    Http::fake(['kasabazaar.test/api/v1/marketplace/membership/plans' => Http::response(['success' => true, 'data' => fakePlans()])]);

    Livewire::test(Membership::class)->call('subscribe', 'plan-basic')->assertRedirect(route('storefront.login'));

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'membership/subscribe'));
});

it('opens the Paystack checkout for the chosen plan', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/membership/plans' => Http::response(['success' => true, 'data' => fakePlans()]),
        'kasabazaar.test/api/v1/marketplace/membership/subscribe' => Http::response(['success' => true, 'data' => ['gateway' => 'paystack', 'authorization_url' => 'https://checkout.paystack.test/abc', 'reference' => 'ref-1']], 201),
        'kasabazaar.test/api/v1/marketplace/membership' => Http::response(['success' => true, 'data' => ['active' => null, 'history' => []]]),
    ]);

    Livewire::test(Membership::class)->call('subscribe', 'plan-exec')->assertRedirect('https://checkout.paystack.test/abc');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/membership/subscribe') && $request['plan_id'] === 'plan-exec');
});

it('shows an active member their plan and the buyers WhatsApp group', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/membership/plans' => Http::response(['success' => true, 'data' => fakePlans()]),
        'kasabazaar.test/api/v1/marketplace/membership' => Http::response(['success' => true, 'data' => [
            'active' => ['id' => 'm-1', 'plan' => fakePlans()[1], 'status' => 'active', 'starts_at' => now()->toIso8601String(), 'expires_at' => now()->addYear()->toIso8601String(), 'days_left' => 365, 'whatsapp_group_url' => 'https://chat.whatsapp.com/example'],
            'history' => [],
        ]]),
    ]);

    Livewire::test(Membership::class)
        ->assertSee('Your membership')
        ->assertSee('365 days left')
        ->assertSee('Renew')
        ->assertSee('https://chat.whatsapp.com/example');
});

// ─── Delivery cost on the order page ──────────────────────────────────────────

function fakeOrder(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 'ord-1', 'order_number' => 'KMB-1', 'order_group_id' => 'grp-1', 'status' => 'paid', 'payment_status' => 'paid',
        'subtotal_ghs' => 100, 'shipping_fee_ghs' => 0, 'tax_ghs' => 0, 'discount_ghs' => 0, 'total_ghs' => 100,
        'delivery_method' => 'delivery', 'delivery_fee_status' => 'awaiting_quote', 'items' => [], 'status_history' => [],
    ], $overrides);
}

function fakeOrderBackend(array $order): void
{
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/orders/ord-1/delivery-fee/pay' => Http::response(['success' => true, 'data' => ['gateway' => 'paystack', 'authorization_url' => 'https://pay.test/fee', 'reference' => 'r1']]),
        'kasabazaar.test/api/v1/marketplace/orders/ord-1' => Http::response(['success' => true, 'data' => $order]),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);
}

it('tells the customer our team is reviewing the items while the delivery cost is pending', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    fakeOrderBackend(fakeOrder());

    Livewire::test(OrderDetail::class, ['order' => 'ord-1'])
        ->assertSee('shipping team is reviewing your items')
        ->assertDontSee('Pay delivery cost');
});

it('lets the customer pay the delivery cost once our team has set it', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    fakeOrderBackend(fakeOrder(['delivery_fee_status' => 'quoted', 'delivery_fee_ghs' => 45.5, 'delivery_fee_notes' => 'Bulky item']));

    Livewire::test(OrderDetail::class, ['order' => 'ord-1'])
        ->assertSee('Delivery cost: GHS 45.50')
        ->assertSee('Bulky item')
        ->call('payDeliveryFee')
        ->assertRedirect('https://pay.test/fee');
});

it('does not start a delivery payment before a cost has been set', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    fakeOrderBackend(fakeOrder());

    Livewire::test(OrderDetail::class, ['order' => 'ord-1'])->call('payDeliveryFee');

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'delivery-fee/pay'));
});

it('shows the planned delivery time once the delivery team has agreed it', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    fakeOrderBackend(fakeOrder(['delivery_fee_status' => 'paid', 'delivery_fee_ghs' => 45.5, 'delivery_scheduled_at' => '2026-12-01T10:00:00+00:00', 'delivery_plan_notes' => 'Call before arriving']));

    Livewire::test(OrderDetail::class, ['order' => 'ord-1'])
        ->assertSee('Delivery cost paid')
        ->assertSee('Delivery planned for')
        ->assertSee('Call before arriving');
});

it('sends a customer back to the order after paying the delivery cost', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/payments/verify/paystack' => Http::response(['success' => true, 'data' => ['type' => 'delivery_fee', 'order_id' => 'ord-1']]),
    ]);

    $this->get(route('storefront.checkout.callback', ['reference' => 'ref-1']))->assertRedirect(route('storefront.account.orders.show', 'ord-1'));
});

// ─── Pickup stations, warehouse flow and returns ──────────────────────────────

function fakeStations(): array
{
    return [
        ['id' => 'st-1', 'name' => 'Accra Pickup', 'country' => 'Ghana', 'city' => 'Accra', 'address' => '12 Airport Road, Accra, Ghana', 'phone' => '0200000001', 'opening_hours' => 'Mon-Sat 9am-6pm', 'instructions' => null],
    ];
}

it('lists pickup stations when the shopper chooses Pickup, and needs one chosen', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    // Stubs registered first win, so the specific stub goes before the catch-all in fakeCheckoutBackend().
    Http::fake(['kasabazaar.test/api/v1/marketplace/pickup-stations*' => Http::response(['success' => true, 'data' => fakeStations()])]);
    fakeCheckoutBackend(deliveryMethods());

    Livewire::test(Checkout::class)
        ->set('deliveryMethod', 'pickup')
        ->assertSee('Choose a pickup station')
        ->assertSee('Accra Pickup')
        ->call('placeOrder')
        ->assertSet('error', 'Please choose the pickup station where you will collect your order.');

    Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), '/marketplace/checkout'));
});

it('sends the chosen pickup station with the order', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    Http::fake(['kasabazaar.test/api/v1/marketplace/pickup-stations*' => Http::response(['success' => true, 'data' => fakeStations()])]);
    fakeCheckoutBackend(deliveryMethods());

    Livewire::test(Checkout::class)->set('deliveryMethod', 'pickup')->set('pickupStationId', 'st-1')->call('placeOrder');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/marketplace/checkout')
        && $request['delivery_method'] === 'pickup' && $request['pickup_station_id'] === 'st-1');
});

it('asks for the USA stations when the shopper switches country', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    Http::fake(['kasabazaar.test/api/v1/marketplace/pickup-stations*' => Http::response(['success' => true, 'data' => []])]);
    fakeCheckoutBackend(deliveryMethods());

    Livewire::test(Checkout::class)->set('deliveryMethod', 'pickup')->set('pickupCountry', 'USA')->assertSee('no pickup stations in USA');

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'pickup-stations') && ($request['country'] ?? null) === 'USA');
});

it('shows the warehouse steps on the order timeline', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    fakeOrderBackend(fakeOrder(['status' => 'at_warehouse', 'delivery_fee_status' => 'not_required']));

    Livewire::test(OrderDetail::class, ['order' => 'ord-1'])
        ->assertSee('Sent to KASAROSE Warehouse')
        ->assertSee('Received at Warehouse')
        ->assertSee('Inspected')
        ->assertSee('Dispatched to You');
});

it('shows pickup orders their station and collected step instead of delivery', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    fakeOrderBackend(fakeOrder([
        'status' => 'ready_for_pickup', 'delivery_method' => 'pickup', 'delivery_fee_status' => 'not_required',
        'pickup_station' => ['name' => 'Accra Pickup', 'address' => '12 Airport Road', 'opening_hours' => null, 'phone' => null],
    ]));

    Livewire::test(OrderDetail::class, ['order' => 'ord-1'])
        ->assertSee('Pickup station')
        ->assertSee('Accra Pickup')
        ->assertSee('Ready to collect')
        ->assertSee('Collected')
        ->assertDontSee('Dispatched to You');
});

it('explains a failed inspection', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    fakeOrderBackend(fakeOrder(['status' => 'inspection_failed', 'delivery_fee_status' => 'not_required', 'inspection_notes' => 'Screen cracked']));

    Livewire::test(OrderDetail::class, ['order' => 'ord-1'])
        ->assertSee('Problem found at inspection');
});

it('lets the customer request a return on a delivered order', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/orders/ord-1/returns' => Http::response(['success' => true, 'data' => ['id' => 'r1', 'return_number' => 'RTN-1', 'status' => 'requested']], 201),
        'kasabazaar.test/api/v1/marketplace/orders/ord-1' => Http::response(['success' => true, 'data' => fakeOrder(['status' => 'delivered', 'delivery_fee_status' => 'not_required'])]),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::test(OrderDetail::class, ['order' => 'ord-1'])
        ->assertSee('Request a return')
        ->set('returnReason', 'Wrong colour')
        ->call('requestReturn')
        ->assertHasNoErrors();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/orders/ord-1/returns') && $request['reason'] === 'Wrong colour');
});

it('shows where to bring a return once it is approved, and hides the request button while one is open', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
    fakeOrderBackend(fakeOrder(['status' => 'delivered', 'delivery_fee_status' => 'not_required', 'returns' => [[
        'id' => 'r1', 'return_number' => 'RTN-1', 'status' => 'approved', 'reason' => 'Wrong colour', 'staff_notes' => null,
        'warehouse' => ['name' => 'Accra Warehouse', 'address' => '12 Airport Road', 'phone' => '0200000001'],
    ]]]));

    Livewire::test(OrderDetail::class, ['order' => 'ord-1'])
        ->assertSee('Accra Warehouse')
        ->assertSee('before it goes back to the vendor')
        ->assertDontSee('Request a return');
});

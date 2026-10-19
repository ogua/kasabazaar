<?php

use App\Livewire\Storefront\Account\OrderDetail;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Storefront\CheckoutCallback;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/*
 * The backend's checkout empties the cart and reserves stock before payment
 * starts. These cover what a shopper can do when payment does not complete:
 * retry from checkout without placing a second order, or pay later from the
 * order page.
 */

beforeEach(function () {
    config(['services.kasabazaar.base_url' => 'https://kasabazaar.test/api/v1']);
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
});

function cartWithOneItem(): array
{
    return ['success' => true, 'data' => ['items' => [['id' => 'i-1', 'quantity' => 1, 'price_ghs' => 10, 'product' => ['name' => 'Kettle']]], 'subtotal_ghs' => 10, 'discount_ghs' => 0]];
}

function pendingOrder(array $overrides = []): array
{
    return array_merge([
        'id' => 'order-1',
        'order_number' => 'KM-0001',
        'order_group_id' => 'group-1',
        'status' => 'awaiting_payment',
        'payment_status' => 'pending',
        'vendor' => ['business_name' => 'Vendor'],
        'items' => [],
        'subtotal_ghs' => 10,
        'shipping_fee_ghs' => 0,
        'discount_ghs' => 0,
        'tax_ghs' => 0,
        'total_ghs' => 10,
        'status_history' => [],
        'created_at' => now()->toIso8601String(),
    ], $overrides);
}

it('retries only the payment when the gateway fails after the order was created', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/cart' => Http::response(cartWithOneItem()),
        'kasabazaar.test/api/v1/marketplace/addresses' => Http::response(['success' => true, 'data' => [['id' => 'addr-1', 'is_default' => true, 'full_name' => 'Ama Mensah', 'phone' => '0241234567', 'street' => null, 'city' => 'Accra', 'region' => 'Greater Accra']]]),
        'kasabazaar.test/api/v1/marketplace/checkout' => Http::response(['success' => true, 'data' => ['id' => 'group-1']], 201),
        'kasabazaar.test/api/v1/marketplace/payments/initiate' => Http::sequence()
            ->push(['success' => false, 'message' => 'Gateway timeout.'], 502)
            ->push(['success' => true, 'data' => ['gateway' => 'paystack', 'authorization_url' => 'https://checkout.paystack.test/abc']]),
    ]);

    $component = Livewire::test(Checkout::class)
        ->call('placeOrder')
        ->assertSet('orderGroupId', 'group-1')
        ->assertSee('Retry payment');

    expect($component->get('error'))->toContain('Your order is saved')->toContain('Gateway timeout.');

    $component->call('placeOrder')->assertRedirect('https://checkout.paystack.test/abc');

    expect(collect(Http::recorded())->filter(fn ($pair) => str_ends_with($pair[0]->url(), '/marketplace/checkout'))->count())->toBe(1);
});

it('lets a shopper complete payment for an unpaid order', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/orders/order-1' => Http::response(['success' => true, 'data' => pendingOrder()]),
        'kasabazaar.test/api/v1/marketplace/payments/initiate' => Http::response(['success' => true, 'data' => ['gateway' => 'paystack', 'authorization_url' => 'https://checkout.paystack.test/xyz']]),
    ]);

    Livewire::test(OrderDetail::class, ['order' => 'order-1'])
        ->assertSee('Payment not completed')
        ->assertSee('Awaiting Payment')
        ->call('payNow')
        ->assertRedirect('https://checkout.paystack.test/xyz');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/payments/initiate') && $request['order_group_id'] === 'group-1');
});

it('offers no payment action once an order is paid', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/orders/order-1' => Http::response(['success' => true, 'data' => pendingOrder(['status' => 'paid', 'payment_status' => 'paid'])]),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::test(OrderDetail::class, ['order' => 'order-1'])
        ->assertDontSee('Payment not completed')
        ->call('payNow')
        ->assertNoRedirect();

    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/payments/initiate'));
});

it('points a failed payment at the unpaid order rather than the emptied cart', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/payments/verify/paystack' => Http::response(['success' => false, 'message' => 'Payment was declined.'], 422),
    ]);

    Livewire::withQueryParams(['reference' => 'ref-1'])
        ->test(CheckoutCallback::class)
        ->assertSet('status', 'failed')
        ->assertSee('Payment was declined.')
        ->assertSee(route('storefront.account.orders', ['status' => 'awaiting_payment']), escape: false)
        ->assertDontSee(route('storefront.checkout'), escape: false);
});

it('verifies a Stripe payment the shopper was redirected back from', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/payments/verify/stripe' => Http::response(['success' => true, 'data' => ['order_group_id' => 'group-1']]),
        'kasabazaar.test/api/v1/marketplace/orders*' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::withQueryParams(['payment_intent' => 'pi_123', 'order_group_id' => 'group-1'])
        ->test(CheckoutCallback::class)
        ->assertSet('status', 'success');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/verify/stripe') && $request['payment_intent_id'] === 'pi_123');
});

it('only accepts the regions shipping zones are defined for', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/cart' => Http::response(cartWithOneItem()),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::test(Checkout::class)
        ->set('full_name', 'Ama Mensah')
        ->set('phone', '0241234567')
        ->set('city', 'Accra')
        ->set('region', 'Greater Acra')
        ->call('saveAddress')
        ->assertHasErrors(['region']);
});

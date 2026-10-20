<?php

use App\Livewire\Storefront\Auth\Login;
use App\Livewire\Storefront\CartPage;
use App\Livewire\Storefront\Checkout;
use App\Models\User;
use App\Services\Kasabazaar\CartApi;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.kasabazaar.base_url' => 'https://kasabazaar.test/api/v1']);
});

function cartItem(array $overrides = []): array
{
    return array_merge([
        'id' => 'item-1',
        'quantity' => 2,
        'price_ghs' => 50,
        'stock' => 10,
        'in_stock' => true,
        'is_available' => true,
        'product' => ['id' => 'p-1', 'name' => 'Shea Butter'],
        'vendor' => ['business_name' => 'Ama Stores'],
    ], $overrides);
}

function cartPayload(array $items, array $overrides = []): array
{
    return ['success' => true, 'data' => array_merge([
        'items' => $items,
        'subtotal_ghs' => collect($items)->sum(fn ($item) => $item['price_ghs'] * $item['quantity']),
        'discount_ghs' => 0,
        'coupon_code' => null,
    ], $overrides)];
}

it('keys a guest cart on its own token, never the session id', function () {
    Http::fake(['kasabazaar.test/*' => Http::response(cartPayload([]))]);

    Livewire::test(CartPage::class);

    $token = session(CartApi::GUEST_TOKEN_KEY);

    expect($token)->toBeString()->not->toBe(session()->getId());
    Http::assertSent(fn ($request) => $request->hasHeader('X-Guest-Session-Id', $token));
    Http::assertNotSent(fn ($request) => $request->hasHeader('X-Guest-Session-Id', session()->getId()));
});

it('hands the guest cart token to the backend on login so the carts merge', function () {
    session()->put(CartApi::GUEST_TOKEN_KEY, 'guest-token-1');

    Http::fake([
        'kasabazaar.test/api/v1/customer/auth/login' => Http::response(['success' => true, 'data' => [
            'token' => 'api-token',
            'user' => ['id' => 'u-1', 'name' => 'Ama Mensah', 'email' => 'ama@example.com'],
        ]]),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'ama@example.com')
        ->set('password', 'secret-password')
        ->call('submit');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/customer/auth/login') && $request->hasHeader('X-Guest-Session-Id', 'guest-token-1'));
    expect(session()->has(CartApi::GUEST_TOKEN_KEY))->toBeFalse();
});

it('refuses quantities above stock without calling the API', function () {
    Http::fake(['kasabazaar.test/*' => Http::response(cartPayload([cartItem(['stock' => 3])]))]);

    Livewire::test(CartPage::class)
        ->call('updateQuantity', 'item-1', '5')
        ->assertDispatched('toast', type: 'error', message: 'Only 3 of this item are available.')
        ->assertSet('quantityRevision', 1);

    Http::assertNotSent(fn ($request) => $request->method() === 'PUT');
});

it('refuses zero, negative and non-numeric quantities', function (mixed $quantity) {
    Http::fake(['kasabazaar.test/*' => Http::response(cartPayload([cartItem()]))]);

    Livewire::test(CartPage::class)
        ->call('updateQuantity', 'item-1', $quantity)
        ->assertSet('quantityRevision', 1);

    Http::assertNotSent(fn ($request) => $request->method() === 'PUT');
})->with(['0', '-2', 'abc', '']);

it('resets the quantity input when the backend rejects a change', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/cart/items/item-1' => Http::response(['success' => false, 'message' => 'Only 2 unit(s) available.'], 422),
        'kasabazaar.test/*' => Http::response(cartPayload([cartItem()])),
    ]);

    Livewire::test(CartPage::class)
        ->call('updateQuantity', 'item-1', '4')
        ->assertDispatched('toast', type: 'error', message: 'Only 2 unit(s) available.')
        ->assertSet('quantityRevision', 1)
        ->assertSet('cart.items.0.quantity', 2);
});

it('uses the cart the API returns instead of reloading it', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/cart/items/item-1' => Http::response(cartPayload([cartItem(['quantity' => 3])])),
        'kasabazaar.test/*' => Http::response(cartPayload([cartItem()])),
    ]);

    Livewire::test(CartPage::class)
        ->call('updateQuantity', 'item-1', '3')
        ->assertSet('cart.items.0.quantity', 3);

    // One GET on mount and one PUT; no follow-up reload.
    Http::assertSentCount(2);
});

it('flags sold-out and over-stock lines and locks checkout', function () {
    Http::fake(['kasabazaar.test/*' => Http::response(cartPayload([
        cartItem(['id' => 'item-1', 'stock' => 0, 'in_stock' => false]),
        cartItem(['id' => 'item-2', 'quantity' => 4, 'stock' => 2, 'in_stock' => false]),
    ]))]);

    Livewire::test(CartPage::class)
        ->assertSee('Sold out. Remove it to continue.')
        ->assertSee('Only 2 left. Lower the quantity to continue.')
        ->assertSee('2 items need your attention before checkout.')
        ->assertDontSee('href="'.route('storefront.checkout').'"', escape: false);
});

it('sends checkout back to the cart while a line is unavailable', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));

    Http::fake([
        'kasabazaar.test/api/v1/marketplace/cart' => Http::response(cartPayload([cartItem(['is_available' => false])])),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::test(Checkout::class)->assertRedirect(route('storefront.cart'));
});

it('shows the applied coupon and removes it', function () {
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));

    Http::fake([
        'kasabazaar.test/api/v1/marketplace/cart/coupon' => Http::response(cartPayload([cartItem()])),
        'kasabazaar.test/*' => Http::response(cartPayload([cartItem()], ['coupon_code' => 'SAVE10', 'discount_ghs' => 10])),
    ]);

    Livewire::test(CartPage::class)
        ->assertSee('SAVE10')
        ->assertSee('-GHS 10.00')
        ->assertSee('GHS 90.00')
        ->call('removeCoupon')
        ->assertSet('cart.coupon_code', null)
        ->assertDontSee('SAVE10');

    Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/marketplace/cart/coupon'));
});

it('asks guests to sign in to use a coupon', function () {
    Http::fake(['kasabazaar.test/*' => Http::response(cartPayload([cartItem()]))]);

    Livewire::test(CartPage::class)
        ->assertSee('to use it. Your cart comes with you.')
        ->assertDontSee('id="coupon-code"', escape: false);
});

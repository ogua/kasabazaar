<?php

use App\Livewire\Storefront\Checkout;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.kasabazaar.base_url' => 'https://kasabazaar.test/api/v1']);
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'token-123']));
});

it('sends a shopper with an empty cart back to the cart page', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/cart' => Http::response(['success' => true, 'data' => ['items' => [], 'subtotal_ghs' => 0]]),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::test(Checkout::class)->assertRedirect(route('storefront.cart'));
});

it('never places an order for an empty cart', function () {
    // The cart had an item when the page loaded, then was emptied in another tab.
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/cart' => Http::response(['success' => true, 'data' => ['items' => [['id' => 'i-1', 'quantity' => 1, 'price_ghs' => 10]], 'subtotal_ghs' => 10]]),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::test(Checkout::class)
        ->set('cart', ['items' => []])
        ->set('selectedAddressId', 'addr-1')
        ->call('placeOrder')
        ->assertSet('error', 'Your cart is empty. Add something before checking out.');

    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/marketplace/checkout'));
});

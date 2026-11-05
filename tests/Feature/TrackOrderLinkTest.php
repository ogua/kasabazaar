<?php

use App\Livewire\Storefront\TrackOrder;
use Livewire\Livewire;

it('routes the path form used in SMS links to the tracker', function () {
    $this->get('/track-order/KMB-20261105-0001')
        ->assertOk()
        ->assertSeeLivewire(TrackOrder::class);
});

it('prefills the order number from the path', function () {
    Livewire::test(TrackOrder::class, ['orderNumber' => 'KMB-20261105-0001'])
        ->assertSet('orderNumber', 'KMB-20261105-0001');
});

it('still prefills the order number from the legacy query string', function () {
    Livewire::withQueryParams(['order_number' => 'KMB-20260902-0001'])
        ->test(TrackOrder::class)
        ->assertSet('orderNumber', 'KMB-20260902-0001');
});

it('opens an empty form without an order number', function () {
    $this->get('/track-order')->assertOk();

    Livewire::test(TrackOrder::class)->assertSet('orderNumber', '');
});

it('keeps generating query-string links for existing route() calls', function () {
    expect(route('storefront.track-order', ['order_number' => 'KMB-1']))
        ->toEndWith('/track-order?order_number=KMB-1');
});

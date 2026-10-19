<?php

use Illuminate\Support\Facades\Http;

/*
 * When the commerce API cannot be reached at all (DNS failure, refused
 * connection, timeout), the storefront degrades page by page instead of
 * returning a 500 from the header's category lookup.
 */

beforeEach(function () {
    Http::fake(['*' => Http::failedConnection()]);
});

it('still serves static pages when the API is unreachable', function (string $route) {
    $this->get(route($route))->assertOk();
})->with([
    'storefront.about',
    'storefront.privacy',
    'storefront.faq',
]);

it('shows a friendly error on the homepage when the API is unreachable', function () {
    $this->get(route('storefront.home'))
        ->assertOk()
        ->assertSee('having trouble loading products', escape: false);
});

it('shows a friendly error on the shop when the API is unreachable', function () {
    $this->get(route('storefront.shop'))
        ->assertOk()
        ->assertSee('having trouble loading products', escape: false);
});

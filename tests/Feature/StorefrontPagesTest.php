<?php

/*
 * Smoke coverage for the static storefront pages — the ones that render without
 * the backend commerce API being reachable. Product, cart and checkout pages are not
 * covered here because they require a live upstream.
 */

it('renders the static storefront pages', function (string $route) {
    $this->get(route($route))->assertOk();
})->with([
    'storefront.about',
    'storefront.group',
    'storefront.faq',
    'storefront.privacy',
    'storefront.terms',
    'storefront.delivery-policy',
    'storefront.returns',
    'storefront.cookies',
]);

it('names every group company on the legal pages that describe data sharing', function (string $route) {
    $response = $this->get(route($route));

    foreach (config('group.companies') as $company) {
        $response->assertSee($company['name'], escape: false);
        $response->assertSee($company['url'], escape: false);
    }
})->with([
    'storefront.privacy',
    'storefront.terms',
    'storefront.cookies',
]);

it('links every sister company from the footer', function () {
    $response = $this->get(route('storefront.about'));

    foreach (config('group.companies') as $company) {
        $response->assertSee($company['url'], escape: false);
    }

    $response->assertSee(route('storefront.group'), escape: false);
});

it('presents KASAROSE LOGISTICS as the parent and never names KasaBazaar', function (string $route) {
    expect(config('group.parent.name'))->toBe('KASAROSE LOGISTICS')
        ->and(config('group.parent.url'))->toBe('https://kasarose.com')
        ->and(array_column(config('group.companies'), 'name'))->not->toContain('KasaBazaar');

    $this->get(route($route))
        ->assertOk()
        ->assertSee('KASAROSE LOGISTICS group', escape: false)
        ->assertDontSee('kasabazaar', escape: false)
        ->assertDontSee('KasaBazaar', escape: false);
})->with([
    'storefront.about',
    'storefront.group',
    'storefront.privacy',
    'storefront.terms',
    'storefront.delivery-policy',
    'storefront.cookies',
]);

it('exposes the current brand name rather than the pre-rebrand one', function () {
    expect(config('app.name'))->toBe('KASAROSE MARKET')
        ->and(config('session.cookie'))->toBe('kasarose_session');

    $this->get(route('storefront.about'))
        ->assertSee('KASAROSE MARKET', escape: false)
        ->assertDontSee('KasaBazaar Market', escape: false);
});

it('omits the registered address entirely when none is configured', function () {
    config()->set('group.contact.address', null);

    $this->get(route('storefront.privacy'))
        ->assertOk()
        ->assertDontSee('registered address is', escape: false);

    $this->get(route('storefront.terms'))
        ->assertOk()
        ->assertDontSee('registered at', escape: false);
});

it('renders the registered address once one is configured', function () {
    config()->set('group.contact.address', '12 Independence Ave, Accra, Ghana');

    $this->get(route('storefront.privacy'))
        ->assertSee('12 Independence Ave, Accra, Ghana', escape: false);

    $this->get(route('storefront.terms'))
        ->assertSee('registered at 12 Independence Ave, Accra, Ghana', escape: false);
});

it('claims a separate operating entity only when one is confirmed', function () {
    config()->set('group.company.legal_entity', null);

    // Without a confirmed entity the copy must not read "KASAROSE is operated by KASAROSE".
    $this->get(route('storefront.terms'))
        ->assertOk()
        ->assertDontSee('operated by <strong>KASAROSE</strong>', escape: false);

    config()->set('group.company.legal_entity', 'KasaRose Limited');

    $this->get(route('storefront.terms'))
        ->assertSee('operated by <strong>KasaRose Limited</strong>', escape: false);

    $this->get(route('storefront.privacy'))
        ->assertSee('operated by <strong>KasaRose Limited</strong>', escape: false);
});

it('serves robots.txt from a route carrying the real app url', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Sitemap: '.route('sitemap'), escape: false)
        ->assertSee('Disallow: /checkout', escape: false);

    expect(public_path('robots.txt'))->not->toBeFile(
        'A static public/robots.txt would take precedence over the route.',
    );
});

it('keeps the brand source art out of the public web root', function () {
    expect(resource_path('brand/kasa.png'))->toBeFile()
        ->and(glob(public_path('images/brand/*.psd')))->toBeEmpty()
        ->and(is_dir(public_path('images/brand/brand')))->toBeFalse();
});

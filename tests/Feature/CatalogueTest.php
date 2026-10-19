<?php

use App\Livewire\Storefront\ProductDetail;
use App\Livewire\Storefront\ProductListing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.kasabazaar.base_url' => 'https://kasabazaar.test/api/v1']);
    Cache::flush();
});

function catalogueProduct(array $overrides = []): array
{
    return array_merge([
        'id' => 'p-1',
        'name' => 'Ceramic Kettle',
        'description' => "A 1.7 litre kettle.\nBoils fast.",
        'price_ghs' => 250,
        'discount_price_ghs' => null,
        'stock' => 4,
        'images' => [['url' => 'https://cdn.test/kettle.jpg']],
        'category' => ['id' => 'c-1', 'name' => 'Home & Kitchen'],
        'vendor' => ['business_name' => 'Ama Stores', 'slug' => 'ama-stores'],
        'specifications' => ['capacity' => '1.7 L', 'power' => '2200 W'],
        'reviews_count' => 0,
        'average_rating' => 0,
    ], $overrides);
}

function fakeCatalogue(array $product): void
{
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/categories' => Http::response(['success' => true, 'data' => [['id' => 'c-1', 'name' => 'Home & Kitchen']]]),
        'kasabazaar.test/api/v1/marketplace/products/p-1/reviews*' => Http::response(['success' => true, 'data' => []]),
        'kasabazaar.test/api/v1/marketplace/products/p-1/related' => Http::response(['success' => true, 'data' => ['similar' => [], 'from_vendor' => []]]),
        'kasabazaar.test/api/v1/marketplace/products/p-1' => Http::response(['success' => true, 'data' => $product]),
        'kasabazaar.test/api/v1/marketplace/products/missing' => Http::response(['success' => false, 'message' => 'No query results'], 404),
        'kasabazaar.test/api/v1/marketplace/products*' => Http::response(['success' => true, 'data' => [], 'meta' => ['total' => 0, 'current_page' => 1, 'last_page' => 1]]),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);
}

it('returns a real 404 for a product that does not exist', function () {
    fakeCatalogue(catalogueProduct());

    $this->get(route('storefront.product', 'missing'))->assertNotFound()->assertSee('Page not found');
});

it('describes the product to search engines and link previews', function () {
    fakeCatalogue(catalogueProduct());

    $this->get(route('storefront.product', 'p-1'))
        ->assertOk()
        ->assertSee('<title>Ceramic Kettle — KASAROSE MARKET</title>', escape: false)
        ->assertSee('<meta property="og:title" content="Ceramic Kettle">', escape: false)
        ->assertSee('<meta property="og:image" content="https://cdn.test/kettle.jpg">', escape: false)
        ->assertSee('<meta property="og:type" content="product">', escape: false)
        ->assertSee('content="GHS 250.00 · A 1.7 litre kettle. Boils fast."', escape: false);
});

it('shows the specifications and the category in the breadcrumb', function () {
    fakeCatalogue(catalogueProduct());

    $this->get(route('storefront.product', 'p-1'))
        ->assertSeeInOrder(['Shop', 'Home &amp; Kitchen', 'Ceramic Kettle'], escape: false)
        ->assertSee('Specifications')
        ->assertSee('2200 W');
});

it('keeps add to cart disabled for an out-of-stock product', function () {
    fakeCatalogue(catalogueProduct(['stock' => 0]));

    $this->get(route('storefront.product', 'p-1'))
        ->assertSee('x-bind:disabled="adding || true"', escape: false)
        ->assertSee('Out of Stock');
});

it('ignores a "discount" price that is not lower than the regular price', function () {
    fakeCatalogue(catalogueProduct(['discount_price_ghs' => 300]));

    $this->get(route('storefront.product', 'p-1'))
        ->assertSee('GHS 250.00')
        ->assertDontSee('Save GHS');
});

it('never lets the quantity exceed known stock', function () {
    fakeCatalogue(catalogueProduct(['stock' => 2]));

    Livewire::test(ProductDetail::class, ['product' => 'p-1'])
        ->call('increment')
        ->call('increment')
        ->call('increment')
        ->assertSet('quantity', 2);
});

it('names a category page after its category', function () {
    fakeCatalogue(catalogueProduct());

    $this->get(route('storefront.category', 'c-1'))
        ->assertOk()
        ->assertSee('<title>Home &amp; Kitchen — KASAROSE MARKET</title>', escape: false)
        ->assertSee('<h1 class="font-display font-bold text-2xl text-navy-900">Home &amp; Kitchen</h1>', escape: false);
});

it('keeps search result pages out of the index', function () {
    fakeCatalogue(catalogueProduct());

    $this->get(route('storefront.shop', ['search' => 'kettle']))
        ->assertSee('<meta name="robots" content="noindex, follow">', escape: false);

    $this->get(route('storefront.shop'))
        ->assertDontSee('<meta name="robots"', escape: false);
});

it('clears one filter or all of them', function () {
    fakeCatalogue(catalogueProduct());

    Livewire::test(ProductListing::class)
        ->set('search', 'kettle')
        ->set('in_stock', true)
        ->set('price_max', 500)
        ->assertSee('Clear all')
        ->call('clearFilter', 'in_stock')
        ->assertSet('in_stock', false)
        ->assertSet('search', 'kettle')
        ->call('clearFilter')
        ->assertSet('search', '')
        ->assertSet('price_max', null);
});

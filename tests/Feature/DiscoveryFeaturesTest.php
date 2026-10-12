<?php

use App\Livewire\Storefront\Shared\CompareTray;
use App\Services\Kasabazaar\VendorApi;
use App\Support\CompareList;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.kasabazaar.base_url' => 'https://kasabazaar.test/api/v1']);
});

function catalogProduct(string $id, array $overrides = []): array
{
    return array_replace_recursive([
        'id' => $id,
        'name' => "Product {$id}",
        'description' => 'A fine product.',
        'sku' => "SKU-{$id}",
        'price_ghs' => 100.0,
        'discount_price_ghs' => null,
        'stock' => 5,
        'images' => [],
        'specifications' => [],
        'category' => ['id' => 'cat-1', 'name' => 'Phones'],
        'vendor' => ['business_name' => 'Accra Gadgets', 'slug' => 'accra-gadgets'],
        'average_rating' => 0,
        'reviews_count' => 0,
    ], $overrides);
}

it('returns search suggestions with storefront links', function () {
    Http::fake(['kasabazaar.test/api/v1/marketplace/search/suggest*' => Http::response(['success' => true, 'data' => [
        'products' => [['id' => 'p-1', 'name' => 'Galaxy A15', 'price_ghs' => 1800, 'image_url' => null, 'vendor' => 'Accra Gadgets']],
        'categories' => [['id' => 'cat-1', 'name' => 'Phones']],
        'auctions' => [['slug' => 'kente-abc', 'title' => 'Vintage kente', 'current_price_ghs' => 300, 'image_url' => null]],
    ]])]);

    $this->getJson(route('search.suggest', ['q' => 'gal']))
        ->assertOk()
        ->assertJsonPath('products.0.name', 'Galaxy A15')
        ->assertJsonPath('products.0.url', route('storefront.product', 'p-1'))
        ->assertJsonPath('categories.0.url', route('storefront.category', 'cat-1'))
        ->assertJsonPath('auctions.0.url', route('storefront.auction', 'kente-abc'));
});

it('does not call the API for one-letter searches and survives an API outage', function () {
    Http::fake(['kasabazaar.test/*' => Http::response(['success' => false, 'message' => 'Down'], 500)]);

    $this->getJson(route('search.suggest', ['q' => 'g']))->assertOk()->assertExactJson(['products' => [], 'categories' => [], 'auctions' => []]);
    Http::assertNothingSent();

    $this->getJson(route('search.suggest', ['q' => 'galaxy']))->assertOk()->assertJsonPath('products', []);
});

it('adds and removes products from the comparison, up to four', function () {
    $component = Livewire::test(CompareTray::class);

    foreach (['a', 'b', 'c', 'd'] as $id) {
        $component->dispatch('toggle-compare', productId: $id, name: "Product {$id}", image: null);
    }

    expect(app(CompareList::class)->ids())->toBe(['a', 'b', 'c', 'd']);

    $component->dispatch('toggle-compare', productId: 'e', name: 'Product e')
        ->assertDispatched('toast', type: 'error');
    expect(app(CompareList::class)->ids())->not->toContain('e');

    $component->dispatch('toggle-compare', productId: 'b', name: 'Product b')
        ->assertDispatched('compare-updated', ids: ['a', 'c', 'd']);
});

it('compares products side by side with the union of their specifications', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/products/p-1' => Http::response(['success' => true, 'data' => catalogProduct('p-1', [
            'name' => 'Galaxy A15', 'price_ghs' => 1800.0, 'specifications' => ['brand' => 'Samsung', 'storage' => '128GB'],
        ])]),
        'kasabazaar.test/api/v1/marketplace/products/p-2' => Http::response(['success' => true, 'data' => catalogProduct('p-2', [
            'name' => 'Spark 20', 'price_ghs' => 1500.0, 'specifications' => ['brand' => 'Tecno', 'battery' => '5000mAh'],
        ])]),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);

    $compare = app(CompareList::class);
    $compare->add('p-1', 'Galaxy A15');
    $compare->add('p-2', 'Spark 20');

    $this->get(route('storefront.compare'))
        ->assertOk()
        ->assertSeeInOrder(['Galaxy A15', 'Spark 20'])
        ->assertSee('Lowest price')
        ->assertSee('Storage')
        ->assertSee('Battery')
        ->assertSee('5000mAh');
});

it('shows related products, share links and product structured data on the product page', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/products/p-1/related' => Http::response(['success' => true, 'data' => [
            'similar' => [catalogProduct('p-2', ['name' => 'Spark 20'])],
            'from_vendor' => [catalogProduct('p-3', ['name' => 'Power Bank'])],
        ]]),
        'kasabazaar.test/api/v1/marketplace/products/p-1/reviews*' => Http::response(['success' => true, 'data' => []]),
        'kasabazaar.test/api/v1/marketplace/products/p-1' => Http::response(['success' => true, 'data' => catalogProduct('p-1', [
            'name' => 'Galaxy A15', 'price_ghs' => 1800.0, 'specifications' => ['brand' => 'Samsung'],
        ])]),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);

    $this->get(route('storefront.product', 'p-1'))
        ->assertOk()
        ->assertSee('Similar items')
        ->assertSee('Spark 20')
        ->assertSee('More from Accra Gadgets')
        ->assertSee('Power Bank')
        ->assertSee('https://wa.me/?text=', false)
        ->assertSee('"@type":"Product"', false)
        ->assertSee('"priceCurrency":"GHS"', false)
        ->assertSee('"name":"Samsung"', false);
});

it('drives the vendor auction workflow through the vendor API', function () {
    Http::fake(['kasabazaar.test/*' => Http::response(['success' => true, 'data' => ['id' => 'auc-1', 'status' => 'pending_review']])]);

    $api = app(VendorApi::class);
    $api->createAuction(['title' => 'Antique stool', 'starting_price_ghs' => 300]);
    $api->submitAuction('auc-1');
    $api->deleteAuctionImage('auc-1', 2);

    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/marketplace/vendor/auctions') && $r['title'] === 'Antique stool');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/marketplace/vendor/auctions/auc-1/submit'));
    Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/marketplace/vendor/auctions/auc-1/images/2'));
});

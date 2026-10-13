<?php

use App\Filament\Vendor\Pages\Auctions\EditAuction;
use App\Filament\Vendor\Pages\Products\EditProduct;
use App\Filament\Vendor\Pages\StoreSettings;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/*
 * Vendor pages forward uploads to the kasabazaar API. Their FileUpload fields
 * are dehydrated(false), so the files never appear in getState(); these tests
 * pin that they are still read and sent.
 */

beforeEach(function () {
    config(['services.kasabazaar.base_url' => 'https://kasabazaar.test/api/v1']);
    Filament::setCurrentPanel(Filament::getPanel('vendor'));
    $this->actingAs(User::factory()->create(['kasabazaar_token' => 'vendor-token']));
});

it('sends new auction photos to the backend when the vendor saves', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/vendor/categories' => Http::response(['success' => true, 'data' => []]),
        'kasabazaar.test/api/v1/marketplace/vendor/auctions/auc-1/images' => Http::response(['success' => true, 'data' => []], 201),
        'kasabazaar.test/api/v1/marketplace/vendor/auctions/auc-1' => Http::response(['success' => true, 'data' => [
            'id' => 'auc-1', 'slug' => 'stool-abc', 'title' => 'Ashanti stool', 'description' => null, 'condition' => null,
            'category_id' => null, 'images' => [], 'starting_price_ghs' => 300, 'reserve_price_ghs' => null,
            'bid_increment_ghs' => 10, 'current_price_ghs' => null, 'bids_count' => 0,
            'starts_at' => now()->addDay()->toIso8601String(), 'ends_at' => now()->addDays(7)->toIso8601String(),
            'extension_minutes' => 2, 'status' => 'draft', 'state' => 'draft', 'outcome' => null, 'is_paid' => false,
            'submitted_at' => null, 'review_notes' => null, 'is_editable' => true,
        ]]),
    ]);

    Livewire::test(EditAuction::class, ['auction' => 'auc-1'])
        ->set('data.newImages', [UploadedFile::fake()->image('stool.jpg')])
        ->call('save');

    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && str_ends_with($r->url(), '/marketplace/vendor/auctions/auc-1/images')
        && $r->isMultipart());
});

it('sends a new product image to the backend when the vendor saves', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/vendor/categories' => Http::response(['success' => true, 'data' => []]),
        'kasabazaar.test/api/v1/marketplace/vendor/products/p-1/images' => Http::response(['success' => true, 'data' => []], 201),
        'kasabazaar.test/api/v1/marketplace/vendor/products/p-1' => Http::response(['success' => true, 'data' => [
            'id' => 'p-1', 'name' => 'Kente scarf', 'sku' => null, 'category' => null, 'description' => '',
            'price_ghs' => 120, 'discount_price_ghs' => null, 'stock' => 4, 'is_active' => true, 'images' => [],
        ]]),
    ]);

    Livewire::test(EditProduct::class, ['product' => 'p-1'])
        ->set('data.newImage', [UploadedFile::fake()->image('scarf.jpg')])
        ->call('save');

    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && str_ends_with($r->url(), '/marketplace/vendor/products/p-1/images')
        && $r->isMultipart());
});

it('sends a new store logo to the backend and shows the stored URL', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/vendor/store/logo' => Http::response(['success' => true, 'data' => ['logo_url' => 'https://kasabazaar.test/storage/vendor-logos/logo.png']], 201),
        'kasabazaar.test/api/v1/marketplace/vendor/store' => Http::response(['success' => true, 'data' => [
            'business_name' => 'Demo Crafts', 'description' => '', 'phone' => '', 'support_email' => '',
            'payout_method' => '', 'payout_details' => '', 'logo_path' => null, 'banner_path' => null,
        ]]),
    ]);

    Livewire::test(StoreSettings::class)
        ->set('data.newLogo', [UploadedFile::fake()->image('logo.png')])
        ->call('save')
        ->assertSet('logoUrl', 'https://kasabazaar.test/storage/vendor-logos/logo.png');

    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && str_ends_with($r->url(), '/marketplace/vendor/store/logo')
        && $r->isMultipart());
});

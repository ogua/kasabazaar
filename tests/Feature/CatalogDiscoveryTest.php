<?php

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\EcommerceCategory;
use App\Models\EcommerceProduct;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogDiscoveryTest extends TestCase
{
    use DatabaseTransactions;

    private function makeVendor(string $name): Vendor
    {
        $owner = User::create([
            'name' => $name.' Owner',
            'email' => Str::slug($name).'-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'role' => 'vendor',
            'status' => 'active',
        ]);

        $vendor = Vendor::create([
            'user_id' => $owner->id,
            'business_name' => $name,
            'commission_rate' => 10,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        VendorWallet::create(['vendor_id' => $vendor->id]);

        return $vendor;
    }

    private function makeCategory(string $name): EcommerceCategory
    {
        $category = new EcommerceCategory;
        $category->forceFill(['name' => $name, 'slug' => Str::slug($name).'-'.uniqid(), 'is_active' => true, 'sort_order' => 0])->save();

        return $category;
    }

    private function makeProduct(Vendor $vendor, ?EcommerceCategory $category, string $name, float $price, array $extra = []): EcommerceProduct
    {
        return EcommerceProduct::create(array_merge([
            'vendor_id' => $vendor->id,
            'category_id' => $category?->id,
            'name' => $name,
            'price_ghs' => $price,
            'stock' => 5,
            'is_active' => true,
        ], $extra));
    }

    public function test_related_returns_similar_items_by_price_and_more_from_the_vendor(): void
    {
        $tvs = $this->makeCategory('Zeta Televisions');
        $kitchen = $this->makeCategory('Zeta Kitchen');
        $vendor = $this->makeVendor('Zeta Electronics');
        $other = $this->makeVendor('Other Store');

        $product = $this->makeProduct($vendor, $tvs, 'Zeta 43 inch TV', 3000);
        $near = $this->makeProduct($other, $tvs, 'Zeta 40 inch TV', 2900);
        $far = $this->makeProduct($other, $tvs, 'Zeta 75 inch TV', 12000);
        $sameVendor = $this->makeProduct($vendor, $kitchen, 'Zeta Blender', 400);
        $this->makeProduct($other, $tvs, 'Zeta Hidden TV', 3000, ['is_active' => false]);

        $response = $this->getJson("/api/v1/marketplace/products/{$product->id}/related")->assertOk();

        $similar = array_column($response->json('data.similar'), 'id');
        $this->assertSame([$near->id, $far->id], $similar);
        $this->assertNotContains($product->id, $similar);
        $this->assertSame([$sameVendor->id], array_column($response->json('data.from_vendor'), 'id'));
    }

    public function test_search_matches_every_word_across_name_category_vendor_and_specifications(): void
    {
        $phones = $this->makeCategory('Qwerty Phones');
        $vendor = $this->makeVendor('Qwerty Mobile Hub');

        $galaxy = $this->makeProduct($vendor, $phones, 'Galaxy A15', 1800, ['specifications' => ['brand' => 'Qwertybrand']]);
        $this->makeProduct($vendor, $phones, 'Spark 20', 1500, ['specifications' => ['brand' => 'Othermaker']]);

        $byBrand = $this->getJson('/api/v1/marketplace/products?search=qwertybrand')->assertOk();
        $this->assertSame([$galaxy->id], array_column($byBrand->json('data'), 'id'));

        $byCategoryAndName = $this->getJson('/api/v1/marketplace/products?search='.urlencode('qwerty galaxy'))->assertOk();
        $this->assertSame([$galaxy->id], array_column($byCategoryAndName->json('data'), 'id'));
    }

    public function test_suggest_returns_products_categories_and_live_auctions(): void
    {
        $category = $this->makeCategory('Xylo Instruments');
        $vendor = $this->makeVendor('Xylo Music');
        $this->makeProduct($vendor, $category, 'Xylo Talking Drum', 650);

        Auction::create([
            'vendor_id' => $vendor->id,
            'title' => 'Xylo vintage djembe',
            'starting_price_ghs' => 200,
            'bid_increment_ghs' => 10,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'status' => AuctionStatus::Published,
        ]);

        $response = $this->getJson('/api/v1/marketplace/search/suggest?q=xylo')->assertOk();

        $this->assertSame(['Xylo Talking Drum'], array_column($response->json('data.products'), 'name'));
        $this->assertSame(650, (int) $response->json('data.products.0.price_ghs'));
        $this->assertContains('Xylo Instruments', array_column($response->json('data.categories'), 'name'));
        $this->assertSame(['Xylo vintage djembe'], array_column($response->json('data.auctions'), 'title'));

        $this->getJson('/api/v1/marketplace/search/suggest?q=x')->assertStatus(422);
    }
}

<?php

namespace Tests\Feature;

use App\Models\EcommerceProduct;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlatformVendorProductTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_product_created_without_a_vendor_id_defaults_to_the_platform_vendor(): void
    {
        $product = EcommerceProduct::create([
            'name' => 'Platform Product '.uniqid(),
            'price_ghs' => 25.00,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->assertSame(Vendor::platform()->id, $product->vendor_id);
    }

    public function test_a_platform_vendor_product_is_visible_on_the_customer_marketplace_catalog(): void
    {
        $product = EcommerceProduct::create([
            'name' => 'Platform Catalog Product '.uniqid(),
            'price_ghs' => 40.00,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/marketplace/products?per_page=100');

        $response->assertSuccessful();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($product->id, $ids);

        $entry = collect($response->json('data'))->firstWhere('id', $product->id);
        $this->assertSame('KasaBazaar Market', $entry['vendor']['business_name']);
    }
}

<?php

namespace Database\Seeders;

use App\Enums\EcommerceInventoryLogType;
use App\Models\EcommerceCategory;
use App\Models\EcommerceInventoryLog;
use App\Models\EcommerceProduct;
use App\Models\EcommerceProductImage;
use Database\Seeders\Concerns\SeedsEcommerceDefaults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EcommerceProductSeeder extends Seeder
{
    use SeedsEcommerceDefaults;

    /**
     * Product catalog keyed by leaf category slug.
     *
     * @var array<string, array<int, array{name: string, price: float, brand?: string}>>
     */
    private array $catalog = [
        'smartphones' => [
            ['name' => 'Samsung Galaxy A15', 'price' => 1800, 'brand' => 'Samsung'],
            ['name' => 'Tecno Spark 20', 'price' => 1500, 'brand' => 'Tecno'],
            ['name' => 'Infinix Hot 40', 'price' => 1400, 'brand' => 'Infinix'],
        ],
        'tablets' => [
            ['name' => 'Samsung Galaxy Tab A9', 'price' => 2200, 'brand' => 'Samsung'],
            ['name' => 'Lenovo Tab M10', 'price' => 1900, 'brand' => 'Lenovo'],
            ['name' => 'Amazon Fire HD 10', 'price' => 1600, 'brand' => 'Amazon'],
        ],
        'phone-accessories' => [
            ['name' => 'Anker 20W Fast Charger', 'price' => 120, 'brand' => 'Anker'],
            ['name' => 'Baseus Wireless Earbuds', 'price' => 250, 'brand' => 'Baseus'],
            ['name' => 'Tempered Glass Screen Protector', 'price' => 30, 'brand' => 'Generic'],
        ],
        'televisions' => [
            ['name' => 'Samsung 43" Smart TV', 'price' => 3200, 'brand' => 'Samsung'],
            ['name' => 'LG 55" 4K UHD TV', 'price' => 5800, 'brand' => 'LG'],
            ['name' => 'Hisense 32" LED TV', 'price' => 1800, 'brand' => 'Hisense'],
        ],
        'audio-sound' => [
            ['name' => 'JBL Flip 6 Bluetooth Speaker', 'price' => 750, 'brand' => 'JBL'],
            ['name' => 'Sony Home Theatre System', 'price' => 2600, 'brand' => 'Sony'],
            ['name' => 'Skullcandy Wireless Headphones', 'price' => 450, 'brand' => 'Skullcandy'],
        ],
        'cameras' => [
            ['name' => 'Canon EOS 1500D DSLR', 'price' => 3800, 'brand' => 'Canon'],
            ['name' => 'GoPro Hero 11', 'price' => 3200, 'brand' => 'GoPro'],
            ['name' => 'Yoosee WiFi Security Camera', 'price' => 380, 'brand' => 'Yoosee'],
        ],
        'kitchen-appliances' => [
            ['name' => 'Binatone 5L Air Fryer', 'price' => 850, 'brand' => 'Binatone'],
            ['name' => 'Ramtons Blender', 'price' => 350, 'brand' => 'Ramtons'],
            ['name' => 'Von Hotpoint Microwave', 'price' => 1100, 'brand' => 'Von Hotpoint'],
        ],
        'furniture' => [
            ['name' => '3-Seater Fabric Sofa', 'price' => 4200, 'brand' => 'Generic'],
            ['name' => 'Study Desk with Chair', 'price' => 950, 'brand' => 'Generic'],
            ['name' => 'Bunk Bed Frame', 'price' => 3600, 'brand' => 'Generic'],
        ],
        'home-decor' => [
            ['name' => 'LED Wall Clock', 'price' => 150, 'brand' => 'Generic'],
            ['name' => 'Decorative Throw Pillow Set', 'price' => 220, 'brand' => 'Generic'],
            ['name' => 'Scented Candle Gift Set', 'price' => 180, 'brand' => 'Generic'],
        ],
        'mens-clothing' => [
            ['name' => "Men's Slim Fit Cotton Shirt", 'price' => 180, 'brand' => 'Generic'],
            ['name' => "Men's Denim Jeans", 'price' => 250, 'brand' => 'Generic'],
            ['name' => 'Kente Print Kaftan', 'price' => 450, 'brand' => 'Generic'],
        ],
        'womens-clothing' => [
            ['name' => 'Ankara Print Dress', 'price' => 320, 'brand' => 'Generic'],
            ['name' => "Women's Chiffon Blouse", 'price' => 180, 'brand' => 'Generic'],
            ['name' => 'Maxi Skirt', 'price' => 220, 'brand' => 'Generic'],
        ],
        'footwear' => [
            ['name' => "Men's Leather Loafers", 'price' => 380, 'brand' => 'Generic'],
            ['name' => "Women's Wedge Sandals", 'price' => 290, 'brand' => 'Generic'],
            ['name' => "Kids' Sneakers", 'price' => 180, 'brand' => 'Generic'],
        ],
        'skincare' => [
            ['name' => 'Nivea Body Lotion 400ml', 'price' => 90, 'brand' => 'Nivea'],
            ['name' => 'Vitamin C Facial Serum', 'price' => 220, 'brand' => 'Generic'],
            ['name' => 'Shea Butter Cream', 'price' => 60, 'brand' => 'Generic'],
        ],
        'haircare' => [
            ['name' => 'Cantu Curling Cream', 'price' => 110, 'brand' => 'Cantu'],
            ['name' => 'Argan Oil Hair Treatment', 'price' => 150, 'brand' => 'Generic'],
            ['name' => 'Wide Tooth Comb Set', 'price' => 40, 'brand' => 'Generic'],
        ],
        'personal-care' => [
            ['name' => 'Electric Shaver', 'price' => 320, 'brand' => 'Generic'],
            ['name' => 'Oral-B Electric Toothbrush', 'price' => 280, 'brand' => 'Oral-B'],
            ['name' => 'Deodorant Gift Pack', 'price' => 95, 'brand' => 'Generic'],
        ],
        'beverages' => [
            ['name' => 'Malt Drink Carton (24 Pack)', 'price' => 180, 'brand' => 'Generic'],
            ['name' => 'Ground Coffee 500g', 'price' => 95, 'brand' => 'Generic'],
            ['name' => 'Fruit Juice Variety Pack', 'price' => 130, 'brand' => 'Generic'],
        ],
        'snacks' => [
            ['name' => 'Plantain Chips Family Pack', 'price' => 45, 'brand' => 'Generic'],
            ['name' => 'Mixed Nuts Jar', 'price' => 75, 'brand' => 'Generic'],
            ['name' => 'Biscuit Assortment Box', 'price' => 60, 'brand' => 'Generic'],
        ],
        'household-supplies' => [
            ['name' => 'Laundry Detergent 5kg', 'price' => 150, 'brand' => 'Generic'],
            ['name' => 'Multi-Surface Cleaner Pack', 'price' => 90, 'brand' => 'Generic'],
            ['name' => 'Toilet Paper Bulk Pack (24 Rolls)', 'price' => 110, 'brand' => 'Generic'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branch = $this->ecommerceBranch();
        $admin = $this->ecommerceAdmin();

        foreach ($this->catalog as $categorySlug => $products) {
            $category = EcommerceCategory::where('branch_id', $branch->id)
                ->where('slug', $categorySlug)
                ->first();

            if (! $category) {
                continue;
            }

            foreach ($products as $data) {
                $stock = fake()->numberBetween(10, 120);
                $slug = Str::slug($data['name']);

                $product = EcommerceProduct::updateOrCreate(
                    ['branch_id' => $branch->id, 'slug' => $slug],
                    [
                        'category_id' => $category->id,
                        'name' => $data['name'],
                        'sku' => strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $data['name']), 0, 10)),
                        'description' => "{$data['name']} — quality product with fast delivery across Ghana.",
                        'specifications' => [
                            'brand' => $data['brand'] ?? 'Generic',
                            'warranty' => '1 Year',
                        ],
                        'price_ghs' => $data['price'],
                        'discount_price_ghs' => fake()->boolean(30) ? round($data['price'] * 0.85, 2) : null,
                        'weight' => fake()->randomFloat(3, 0.1, 5),
                        'stock' => $stock,
                        'low_stock_threshold' => 5,
                        'is_active' => true,
                        'is_featured' => fake()->boolean(20),
                    ]
                );

                if ($product->images()->count() === 0) {
                    $imageCount = fake()->numberBetween(1, 3);

                    for ($i = 0; $i < $imageCount; $i++) {
                        EcommerceProductImage::create([
                            'ecommerce_product_id' => $product->id,
                            'path' => "ecommerce/products/{$slug}-{$i}.jpg",
                            'sort_order' => $i,
                            'is_primary' => $i === 0,
                        ]);
                    }
                }

                if (! EcommerceInventoryLog::where('ecommerce_product_id', $product->id)->exists()) {
                    EcommerceInventoryLog::create([
                        'ecommerce_product_id' => $product->id,
                        'branch_id' => $branch->id,
                        'type' => EcommerceInventoryLogType::Increase,
                        'quantity_change' => $stock,
                        'quantity_before' => 0,
                        'quantity_after' => $stock,
                        'reason' => 'Initial stock intake',
                        'created_by' => $admin->id,
                    ]);
                }
            }
        }
    }
}

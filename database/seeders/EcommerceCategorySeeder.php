<?php

namespace Database\Seeders;

use App\Models\EcommerceCategory;
use Database\Seeders\Concerns\SeedsEcommerceDefaults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EcommerceCategorySeeder extends Seeder
{
    use SeedsEcommerceDefaults;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branch = $this->ecommerceBranch();

        $tree = [
            'Phones & Tablets' => ['Smartphones', 'Tablets', 'Phone Accessories'],
            'Electronics' => ['Televisions', 'Audio & Sound', 'Cameras'],
            'Home & Kitchen' => ['Kitchen Appliances', 'Furniture', 'Home Decor'],
            'Fashion' => ["Men's Clothing", "Women's Clothing", 'Footwear'],
            'Health & Beauty' => ['Skincare', 'Haircare', 'Personal Care'],
            'Groceries' => ['Beverages', 'Snacks', 'Household Supplies'],
        ];

        $sortOrder = 0;

        foreach ($tree as $parentName => $children) {
            $parent = EcommerceCategory::updateOrCreate(
                ['branch_id' => $branch->id, 'slug' => Str::slug($parentName)],
                [
                    'name' => $parentName,
                    'description' => "Shop the best {$parentName} on Kasabazaar, delivered across Ghana.",
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                ]
            );

            $childSort = 0;

            foreach ($children as $childName) {
                EcommerceCategory::updateOrCreate(
                    ['branch_id' => $branch->id, 'slug' => Str::slug($childName)],
                    [
                        'name' => $childName,
                        'parent_id' => $parent->id,
                        'description' => "{$childName} available with fast delivery across Ghana.",
                        'is_active' => true,
                        'sort_order' => $childSort++,
                    ]
                );
            }
        }
    }
}

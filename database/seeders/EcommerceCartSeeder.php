<?php

namespace Database\Seeders;

use App\Models\EcommerceCart;
use App\Models\EcommerceCartItem;
use App\Models\EcommerceProduct;
use Database\Seeders\Concerns\SeedsEcommerceDefaults;
use Illuminate\Database\Seeder;

class EcommerceCartSeeder extends Seeder
{
    use SeedsEcommerceDefaults;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branch = $this->ecommerceBranch();
        $customers = $this->ecommerceCustomers();
        $products = EcommerceProduct::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->get();

        if ($products->isEmpty()) {
            return;
        }

        // Only half the customers currently have an active cart, which is realistic.
        foreach ($customers->take((int) ceil($customers->count() / 2)) as $customer) {
            $cart = EcommerceCart::updateOrCreate(
                ['user_id' => $customer->id],
                ['branch_id' => $branch->id]
            );

            if ($cart->items()->count() > 0) {
                continue;
            }

            $cartProducts = $products->random(min(fake()->numberBetween(1, 4), $products->count()));

            foreach ($cartProducts as $product) {
                EcommerceCartItem::updateOrCreate(
                    ['cart_id' => $cart->id, 'ecommerce_product_id' => $product->id],
                    [
                        'quantity' => fake()->numberBetween(1, 3),
                        'price_ghs' => $product->discount_price_ghs ?? $product->price_ghs,
                    ]
                );
            }
        }
    }
}

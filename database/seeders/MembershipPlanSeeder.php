<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

/**
 * Starting membership categories. The prices and discounts are PLACEHOLDERS
 * until KASAROSE MARKET confirms them: staff edit them under E-Commerce →
 * Membership Plans, and re-running this seeder never overwrites an edit.
 */
class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['tier' => 'basic', 'name' => 'Basic', 'price_ghs' => 50, 'discount' => 2, 'auctions' => false, 'sort' => 1,
                'description' => 'Join the buyers community and start saving.',
                'perks' => ['2% off marketplace products', 'Access to the buyers WhatsApp group']],
            ['tier' => 'standard', 'name' => 'Standard', 'price_ghs' => 150, 'discount' => 5, 'auctions' => false, 'sort' => 2,
                'description' => 'Better savings for regular shoppers.',
                'perks' => ['5% off marketplace products', 'Access to the buyers WhatsApp group']],
            ['tier' => 'economic', 'name' => 'Economic', 'price_ghs' => 300, 'discount' => 8, 'auctions' => true, 'sort' => 3,
                'description' => 'Bigger discounts and a seat at exclusive auctions.',
                'perks' => ['8% off marketplace products', 'Exclusive member auctions', 'Access to the buyers WhatsApp group']],
            ['tier' => 'executive', 'name' => 'Executive', 'price_ghs' => 600, 'discount' => 12, 'auctions' => true, 'sort' => 4,
                'description' => 'The best of KASAROSE MARKET: top discounts and every exclusive auction.',
                'perks' => ['12% off marketplace products', 'Every exclusive member auction', 'Access to the buyers WhatsApp group']],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::firstOrCreate(['tier' => $plan['tier']], [
                'name' => $plan['name'],
                'description' => $plan['description'],
                'price_ghs' => $plan['price_ghs'],
                'duration_months' => 12,
                'product_discount_percent' => $plan['discount'],
                'exclusive_auctions' => $plan['auctions'],
                'perks' => $plan['perks'],
                'sort_order' => $plan['sort'],
            ]);
        }
    }
}

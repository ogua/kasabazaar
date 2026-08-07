<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EcommerceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            EcommerceCategorySeeder::class,
            EcommerceProductSeeder::class,
            DeliveryAddressSeeder::class,
            EcommerceCartSeeder::class,
            EcommerceOrderSeeder::class,
        ]);
    }
}

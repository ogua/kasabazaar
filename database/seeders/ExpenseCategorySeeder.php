<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Customs & Duties',
                'code' => 'CUSTOMS',
                'description' => 'Import duties, customs clearance fees, and related charges',
                'is_active' => true,
            ],
            [
                'name' => 'Transportation',
                'code' => 'TRANSPORT',
                'description' => 'Local and international transportation costs',
                'is_active' => true,
            ],
            [
                'name' => 'Storage & Warehousing',
                'code' => 'STORAGE',
                'description' => 'Warehouse fees, storage charges, and handling',
                'is_active' => true,
            ],
            [
                'name' => 'Documentation',
                'code' => 'DOCUMENTATION',
                'description' => 'Bill of lading, permits, certificates, and paperwork',
                'is_active' => true,
            ],
            [
                'name' => 'Insurance',
                'code' => 'INSURANCE',
                'description' => 'Cargo insurance and shipment protection',
                'is_active' => true,
            ],
            [
                'name' => 'Port Charges',
                'code' => 'PORT',
                'description' => 'Port handling, terminal fees, and dock charges',
                'is_active' => true,
            ],
            [
                'name' => 'Packaging',
                'code' => 'PACKAGING',
                'description' => 'Boxes, pallets, wrapping, and packaging materials',
                'is_active' => true,
            ],
            [
                'name' => 'Agent Fees',
                'code' => 'AGENT',
                'description' => 'Clearing agent fees and broker commissions',
                'is_active' => true,
            ],
            [
                'name' => 'Fuel',
                'code' => 'FUEL',
                'description' => 'Vehicle fuel costs for deliveries',
                'is_active' => true,
            ],
            [
                'name' => 'Maintenance',
                'code' => 'MAINTENANCE',
                'description' => 'Vehicle and equipment maintenance',
                'is_active' => true,
            ],
            [
                'name' => 'Communication',
                'code' => 'COMMUNICATION',
                'description' => 'Phone, internet, and communication costs',
                'is_active' => true,
            ],
            [
                'name' => 'Miscellaneous',
                'code' => 'MISC',
                'description' => 'Other shipment-related expenses',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }
}

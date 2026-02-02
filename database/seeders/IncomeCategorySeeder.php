<?php

namespace Database\Seeders;

use App\Models\IncomeCategory;
use Illuminate\Database\Seeder;

class IncomeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Consulting Services',
                'code' => 'CONSULTING',
                'description' => 'Shipping consulting and advisory services',
                'is_active' => true,
            ],
            [
                'name' => 'Equipment Rental',
                'code' => 'RENTAL',
                'description' => 'Rental of containers, pallets, and equipment',
                'is_active' => true,
            ],
            [
                'name' => 'Commission',
                'code' => 'COMMISSION',
                'description' => 'Referral commissions and partner earnings',
                'is_active' => true,
            ],
            [
                'name' => 'Interest Income',
                'code' => 'INTEREST',
                'description' => 'Bank interest and investment returns',
                'is_active' => true,
            ],
            [
                'name' => 'Insurance Refund',
                'code' => 'INSURANCE_REFUND',
                'description' => 'Refunds from insurance claims',
                'is_active' => true,
            ],
            [
                'name' => 'Customs Refund',
                'code' => 'CUSTOMS_REFUND',
                'description' => 'Refunds from overpaid customs duties',
                'is_active' => true,
            ],
            [
                'name' => 'Late Payment Fees',
                'code' => 'LATE_FEES',
                'description' => 'Fees charged for late payments by clients',
                'is_active' => true,
            ],
            [
                'name' => 'Storage Fees',
                'code' => 'STORAGE',
                'description' => 'Extended storage fees charged to clients',
                'is_active' => true,
            ],
            [
                'name' => 'Documentation Fees',
                'code' => 'DOCUMENTATION',
                'description' => 'Fees for documentation services',
                'is_active' => true,
            ],
            [
                'name' => 'Repackaging Fees',
                'code' => 'REPACKAGING',
                'description' => 'Fees for special packaging services',
                'is_active' => true,
            ],
            [
                'name' => 'Handling Fees',
                'code' => 'HANDLING',
                'description' => 'Special handling and processing fees',
                'is_active' => true,
            ],
            [
                'name' => 'Other Income',
                'code' => 'OTHER',
                'description' => 'Miscellaneous income sources',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            IncomeCategory::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }
}

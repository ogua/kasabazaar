<?php

namespace Database\Seeders;

use App\Models\StaffRole;
use Illuminate\Database\Seeder;

class StaffRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Chief Executive Officer',
                'code' => 'CEO',
                'description' => 'Top executive with full access to all system features and reports',
                'base_salary' => 10000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Shipping Manager',
                'code' => 'SHIP_MGR',
                'description' => 'Manages shipping operations, clients, and deliveries',
                'base_salary' => 3000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Accountant',
                'code' => 'ACCOUNTANT',
                'description' => 'Handles payments, expenses, income, and financial reports',
                'base_salary' => 2500.00,
                'is_active' => true,
            ],
            [
                'name' => 'Driver',
                'code' => 'DRIVER',
                'description' => 'Handles delivery trips and shipment transportation',
                'base_salary' => 1500.00,
                'is_active' => true,
            ],
            [
                'name' => 'Warehouse Staff',
                'code' => 'WAREHOUSE',
                'description' => 'Manages inventory and shipment items in warehouse',
                'base_salary' => 1200.00,
                'is_active' => true,
            ],
            [
                'name' => 'Administrative Officer',
                'code' => 'ADMIN',
                'description' => 'Handles user management and system settings',
                'base_salary' => 2000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Customer Service',
                'code' => 'CUSTOMER_SVC',
                'description' => 'Handles client inquiries and support',
                'base_salary' => 1800.00,
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            StaffRole::updateOrCreate(
                ['code' => $role['code']],
                $role
            );
        }
    }
}

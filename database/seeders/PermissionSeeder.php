<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Branch
            'view_any_branch', 'update_branch',

            // Client
            'view_any_client', 'view_client', 'create_client', 'update_client', 'delete_client',

            // Shipment
            'view_any_shipment', 'view_shipment', 'create_shipment', 'update_shipment', 'delete_shipment',

            // Invoice
            'view_any_invoice', 'view_invoice', 'update_invoice',

            // Payment
            'view_any_payment', 'view_payment', 'create_payment',

            // Product
            'view_any_product', 'view_product', 'create_product', 'update_product', 'delete_product',

            // Quotation
            'view_any_quotation', 'view_quotation', 'create_quotation', 'update_quotation', 'delete_quotation',

            // Staff
            'view_any_staff', 'view_staff', 'create_staff', 'update_staff', 'delete_staff',

            // Staff Role
            'view_any_staff_role', 'view_staff_role', 'create_staff_role',

            // User
            'view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user',

            // Expense
            'view_any_expense', 'view_expense', 'create_expense', 'update_expense', 'delete_expense',

            // Income
            'view_any_income', 'view_income', 'create_income', 'update_income', 'delete_income',

            // Payroll
            'view_any_payroll_period', 'view_payroll_period', 'create_payroll_period', 'update_payroll_period', 'delete_payroll_period',

            // Vehicle
            'view_any_vehicle', 'view_vehicle', 'create_vehicle', 'update_vehicle', 'delete_vehicle',

            // Trip
            'view_any_trip', 'view_trip', 'create_trip', 'update_trip', 'delete_trip',

            // Pickup Schedule
            'view_any_pickup_schedule', 'view_pickup_schedule', 'create_pickup_schedule', 'update_pickup_schedule',

            // Exchange Rate
            'view_any_exchange_rate_log', 'create_exchange_rate_log',

            // Cashbook
            'view_any_cashbook_entry', 'view_cashbook_entry', 'create_cashbook_entry', 'update_cashbook_entry', 'delete_cashbook_entry',

            // Reports
            'view_any_report', 'view_report',

            // Customer Feedback
            'view_any_customer_feedback', 'view_customer_feedback', 'update_customer_feedback',

            // Contact Messages
            'view_any_contact_message', 'view_contact_message', 'update_contact_message',
        ];

        // Create any that don't exist yet
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'sanctum']);
        }

        // Give all permissions to super_admin
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $superAdmin->syncPermissions($permissions);

        $this->command->info('Permissions seeded and assigned to super_admin.');
    }
}

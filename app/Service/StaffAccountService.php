<?php

namespace App\Service;

use App\Models\User;
use App\Models\Staff;
use App\Models\StaffRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class StaffAccountService
{
    /**
     * Create a user account for a staff member
     */
    public function createStaffAccount(Staff $staff, string $email, ?string $password = null): User
    {
        $password = $password ?? Str::random(12);

        $user = User::create([
            'name' => $staff->name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // Link user to staff
        $staff->update(['user_id' => $user->id]);

        // Assign permissions based on role
        if ($staff->role) {
            $this->assignRolePermissions($user, $staff->role);
        }

        return $user;
    }

    /**
     * Link an existing user to a staff member
     */
    public function linkUserToStaff(User $user, Staff $staff): void
    {
        $staff->update(['user_id' => $user->id]);

        // Assign permissions based on role
        if ($staff->role) {
            $this->assignRolePermissions($user, $staff->role);
        }
    }

    /**
     * Assign role-based permissions to a user
     */
    public function assignRolePermissions(User $user, StaffRole $staffRole): void
    {
        // Map staff role codes to Spatie roles/permissions
        $rolePermissions = $this->getRolePermissionsMap();

        $permissions = $rolePermissions[$staffRole->code] ?? [];

        // Check if corresponding Spatie role exists
        $roleName = strtolower(str_replace('_', '-', $staffRole->code));
        $spatieRole = Role::where('name', $roleName)->first();

        if ($spatieRole) {
            $user->assignRole($spatieRole);
        }

        // Assign individual permissions
        foreach ($permissions as $permission) {
            try {
                $user->givePermissionTo($permission);
            } catch (\Exception $e) {
                // Permission doesn't exist, skip
                logger()->warning("Permission '{$permission}' not found for role '{$staffRole->code}'");
            }
        }
    }

    /**
     * Get role to permissions mapping
     */
    protected function getRolePermissionsMap(): array
    {
        return [
            'CEO' => [
                // Full access - typically handled by super_admin role
                'view_any_shipment', 'view_shipment', 'create_shipment', 'update_shipment', 'delete_shipment',
                'view_any_client', 'view_client', 'create_client', 'update_client', 'delete_client',
                'view_any_payment', 'view_payment', 'create_payment', 'update_payment', 'delete_payment',
                'view_any_expense', 'view_expense', 'create_expense', 'update_expense', 'delete_expense',
                'view_any_income', 'view_income', 'create_income', 'update_income', 'delete_income',
                'view_any_staff', 'view_staff', 'create_staff', 'update_staff', 'delete_staff',
                'view_any_payroll', 'view_payroll', 'create_payroll', 'update_payroll', 'delete_payroll',
                'view_any_report', 'view_report', 'create_report',
                'view_any_vehicle', 'view_vehicle', 'create_vehicle', 'update_vehicle', 'delete_vehicle',
                'view_any_trip', 'view_trip', 'create_trip', 'update_trip', 'delete_trip',
            ],
            'SHIP_MGR' => [
                'view_any_shipment', 'view_shipment', 'create_shipment', 'update_shipment',
                'view_any_client', 'view_client', 'create_client', 'update_client',
                'view_any_receiver', 'view_receiver', 'create_receiver', 'update_receiver',
                'view_any_payment', 'view_payment', 'create_payment',
                'view_any_expense', 'view_expense', 'create_expense',
                'view_any_vehicle', 'view_vehicle',
                'view_any_trip', 'view_trip', 'create_trip', 'update_trip',
                'view_any_report', 'view_report',
            ],
            'ACCOUNTANT' => [
                'view_any_payment', 'view_payment', 'create_payment', 'update_payment',
                'view_any_expense', 'view_expense', 'create_expense', 'update_expense',
                'view_any_income', 'view_income', 'create_income', 'update_income',
                'view_any_payroll', 'view_payroll', 'create_payroll', 'update_payroll',
                'view_any_report', 'view_report', 'create_report',
                'view_any_shipment', 'view_shipment',
                'view_any_client', 'view_client',
            ],
            'DRIVER' => [
                'view_any_trip', 'view_trip', 'update_trip',
                'view_any_shipment', 'view_shipment',
                'view_any_vehicle', 'view_vehicle',
            ],
            'WAREHOUSE' => [
                'view_any_shipment', 'view_shipment', 'update_shipment',
                'view_any_inventory', 'view_inventory', 'create_inventory', 'update_inventory',
                'view_any_receiver', 'view_receiver',
            ],
            'ADMIN' => [
                'view_any_user', 'view_user', 'create_user', 'update_user',
                'view_any_staff', 'view_staff', 'create_staff', 'update_staff',
                'view_any_branch', 'view_branch',
            ],
            'CUSTOMER_SVC' => [
                'view_any_shipment', 'view_shipment',
                'view_any_client', 'view_client', 'create_client', 'update_client',
                'view_any_payment', 'view_payment',
                'view_any_receiver', 'view_receiver',
            ],
        ];
    }

    /**
     * Revoke all permissions and roles from a user
     */
    public function revokeAllPermissions(User $user): void
    {
        $user->syncRoles([]);
        $user->syncPermissions([]);
    }

    /**
     * Update user permissions based on staff role change
     */
    public function updateUserPermissions(User $user, StaffRole $newRole): void
    {
        // Revoke existing permissions
        $this->revokeAllPermissions($user);

        // Assign new permissions
        $this->assignRolePermissions($user, $newRole);
    }
}

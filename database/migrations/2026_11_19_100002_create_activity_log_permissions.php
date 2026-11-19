<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Read access to the audit trail (Audit & Security → Activity Log and the History actions).
     * Granted to super_admin, which has no Shield gate bypass; give it to other roles in
     * Roles & Permissions as needed.
     */
    public function up(): void
    {
        $permissions = collect(['view_any_activity::log', 'view_activity::log'])
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        Role::query()->where('name', 'super_admin')->where('guard_name', 'web')->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', ['view_any_activity::log', 'view_activity::log'])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

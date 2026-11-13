<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Whoever holds this permission can enter delivery costs for marketplace orders and plan
     * deliveries with customers. Assign it to the shipping/delivery team lead's role.
     */
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'set_delivery_cost', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'set_delivery_cost')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

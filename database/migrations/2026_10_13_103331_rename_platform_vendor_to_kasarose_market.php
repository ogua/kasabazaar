<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data-only: the marketplace is KASAROSE MARKET (the parent company is
 * KASAROSE LOGISTICS), so the platform vendor — products sold directly by the
 * marketplace — carries the marketplace's name. Slug unchanged: Vendor::platform()
 * resolves it through config('ecommerce.platform_vendor_slug').
 */
return new class extends Migration
{
    public function up(): void
    {
        $vendor = DB::table('vendors')
            ->where('slug', config('ecommerce.platform_vendor_slug'))
            ->where('business_name', 'KASAROSE')
            ->first(['id', 'user_id']);

        if (! $vendor) {
            return;
        }

        DB::table('vendors')->where('id', $vendor->id)->update(['business_name' => 'KASAROSE MARKET', 'updated_at' => now()]);
        DB::table('users')->where('id', $vendor->user_id)->update(['name' => 'KASAROSE MARKET', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Not reversed: the previous name described a structure that no longer exists.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data-only migration: seeds a single "KasaBazaar Market" platform Vendor
 * (+ owning User + wallet) representing products sold directly by the
 * platform rather than a third-party vendor, then backfills any
 * ecommerce_products left with vendor_id = NULL (e.g. created through the
 * admin panel, which has no vendor selector) to point at it. Mirrors the
 * pattern used by 2026_08_07_000013_backfill_vendor_data_for_existing_branches.
 */
return new class extends Migration
{
    public function up(): void
    {
        $slug = config('ecommerce.platform_vendor_slug');

        $vendorId = DB::table('vendors')->where('slug', $slug)->value('id');

        if (! $vendorId) {
            $vendorId = $this->createPlatformVendor($slug);
        }

        DB::table('ecommerce_products')
            ->whereNull('vendor_id')
            ->update(['vendor_id' => $vendorId]);
    }

    public function down(): void
    {
        // Data backfill is not reversed: reverting the schema migrations that
        // introduced vendor_id is enough to make this row inert.
    }

    private function createPlatformVendor(string $slug): string
    {
        $userId = (string) Str::uuid();
        $vendorId = (string) Str::uuid();
        $now = now();

        DB::table('users')->insert([
            'id' => $userId,
            'name' => 'KasaBazaar Market',
            'email' => 'platform@kasabazaar.internal',
            'password' => bcrypt(Str::random(40)),
            'role' => 'vendor',
            'status' => 'active',
            'branch_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('vendors')->insert([
            'id' => $vendorId,
            'user_id' => $userId,
            'branch_id' => null,
            'business_name' => 'KasaBazaar Market',
            'slug' => $slug,
            'commission_rate' => 0,
            'status' => 'active',
            'approved_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('users')->where('id', $userId)->update(['vendor_id' => $vendorId]);

        DB::table('vendor_wallets')->insert([
            'id' => (string) Str::uuid(),
            'vendor_id' => $vendorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $vendorId;
    }
};

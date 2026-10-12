<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data-only migration: KasaBazaar left the group in October 2026 and the
 * marketplace is now KASAROSE, so the two platform-owned vendors that still
 * carry the old name on the storefront are renamed:
 *
 * - the per-branch "Kasabazaar Main Branch (Platform Direct)" vendor backfilled
 *   by 2026_08_07_000013 becomes "KASAROSE Official Store", with a matching slug
 *   (its storefront URL is /vendor/{slug});
 * - the "KasaBazaar Market" platform vendor becomes "KASAROSE". Its slug is left
 *   alone because Vendor::platform() resolves it through
 *   config('ecommerce.platform_vendor_slug').
 *
 * The owning vendor users are renamed with them so the vendor portal greets the
 * new name.
 */
return new class extends Migration
{
    private const OFFICIAL_STORE_NAME = 'KASAROSE Official Store';

    private const PLATFORM_VENDOR_NAME = 'KASAROSE';

    public function up(): void
    {
        $this->renameBranchPlatformDirectVendors();
        $this->renamePlatformVendor();
    }

    public function down(): void
    {
        // Not reversed: the old names belong to a company that is no longer
        // part of the group and must not be republished.
    }

    private function renameBranchPlatformDirectVendors(): void
    {
        $vendors = DB::table('vendors')
            ->where('business_name', 'like', 'Kasabazaar Main Branch%')
            ->orderBy('created_at')
            ->get(['id', 'user_id']);

        foreach ($vendors as $index => $vendor) {
            $baseSlug = Str::slug(self::OFFICIAL_STORE_NAME);
            $slug = $index === 0 && ! DB::table('vendors')->where('slug', $baseSlug)->exists()
                ? $baseSlug
                : $baseSlug.'-'.substr($vendor->id, 0, 8);

            DB::table('vendors')->where('id', $vendor->id)->update([
                'business_name' => self::OFFICIAL_STORE_NAME,
                'slug' => $slug,
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $vendor->user_id)->update([
                'name' => self::OFFICIAL_STORE_NAME,
                'updated_at' => now(),
            ]);
        }
    }

    private function renamePlatformVendor(): void
    {
        $vendor = DB::table('vendors')
            ->where('slug', config('ecommerce.platform_vendor_slug'))
            ->where('business_name', 'KasaBazaar Market')
            ->first(['id', 'user_id']);

        if (! $vendor) {
            return;
        }

        DB::table('vendors')->where('id', $vendor->id)->update([
            'business_name' => self::PLATFORM_VENDOR_NAME,
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $vendor->user_id)->update([
            'name' => self::PLATFORM_VENDOR_NAME,
            'updated_at' => now(),
        ]);
    }
};

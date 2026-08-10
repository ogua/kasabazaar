<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data-only migration (gap D.4 in the multi-vendor plan): backfills a "Platform
 * Direct" Vendor + owning User per Branch that already has ecommerce products,
 * then points those products' vendor_id at it so nothing goes vendor-less once
 * branch-scoped tenancy is replaced by vendor-scoped tenancy. Also de-duplicates
 * ecommerce_categories that share the same name across branches (previously
 * distinct rows per branch, now a single global row) and repoints products at
 * the surviving row before the branch-scoped uniqueness is gone for good.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->backfillVendorsForBranchesWithProducts();
        $this->deduplicateCategoriesByName();
    }

    public function down(): void
    {
        // Data backfill is not reversed: reverting the schema migrations that
        // follow it is enough to make these rows inert (vendor_id/columns drop).
    }

    private function backfillVendorsForBranchesWithProducts(): void
    {
        $branchIds = DB::table('ecommerce_products')
            ->whereNull('vendor_id')
            ->whereNotNull('branch_id')
            ->distinct()
            ->pluck('branch_id');

        foreach ($branchIds as $branchId) {
            $branch = DB::table('branches')->where('id', $branchId)->first();

            if (! $branch) {
                continue;
            }

            $userId = (string) Str::uuid();
            $vendorId = (string) Str::uuid();
            $now = now();

            DB::table('users')->insert([
                'id' => $userId,
                'name' => $branch->name.' (Platform Direct)',
                'email' => 'vendor+'.Str::slug($branch->name).'-'.substr($userId, 0, 8).'@platform.internal',
                'password' => bcrypt(Str::random(40)),
                'role' => 'vendor',
                'status' => 'active',
                'branch_id' => $branchId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('vendors')->insert([
                'id' => $vendorId,
                'user_id' => $userId,
                'branch_id' => $branchId,
                'business_name' => $branch->name.' (Platform Direct)',
                'slug' => Str::slug($branch->name).'-platform-direct-'.substr($vendorId, 0, 8),
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

            DB::table('ecommerce_products')
                ->where('branch_id', $branchId)
                ->whereNull('vendor_id')
                ->update(['vendor_id' => $vendorId]);
        }
    }

    private function deduplicateCategoriesByName(): void
    {
        $categories = DB::table('ecommerce_categories')->orderBy('created_at')->get();

        $canonicalByName = [];
        $remap = [];

        foreach ($categories as $category) {
            $key = Str::lower(trim($category->name));

            if (! isset($canonicalByName[$key])) {
                $canonicalByName[$key] = $category->id;

                continue;
            }

            $remap[$category->id] = $canonicalByName[$key];
        }

        foreach ($remap as $duplicateId => $canonicalId) {
            DB::table('ecommerce_products')->where('category_id', $duplicateId)->update(['category_id' => $canonicalId]);
            DB::table('ecommerce_categories')->where('parent_id', $duplicateId)->update(['parent_id' => $canonicalId]);
            DB::table('ecommerce_categories')->where('id', $duplicateId)->delete();
        }

        $this->deduplicateRemainingSlugCollisions();
    }

    /**
     * Safety net for categories with different names that happen to slugify to
     * the same string (name-based dedup above only catches identical names) —
     * the upcoming global unique(slug) migration would otherwise fail.
     */
    private function deduplicateRemainingSlugCollisions(): void
    {
        $bySlug = DB::table('ecommerce_categories')->orderBy('created_at')->get()->groupBy('slug');

        foreach ($bySlug as $slug => $rows) {
            if ($rows->count() <= 1) {
                continue;
            }

            foreach ($rows->skip(1) as $duplicate) {
                DB::table('ecommerce_categories')
                    ->where('id', $duplicate->id)
                    ->update(['slug' => $slug.'-'.substr($duplicate->id, 0, 8)]);
            }
        }
    }
};

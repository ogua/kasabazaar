<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_categories', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'slug']);
        });

        Schema::table('ecommerce_categories', function (Blueprint $table) {
            $table->foreignUuid('branch_id')->nullable()->change();
        });

        // NOTE: the global unique(slug) constraint is added later, in
        // 2026_08_07_000014_add_unique_slug_to_ecommerce_categories_table.php,
        // after the 000013 backfill migration has deduplicated any categories
        // that previously had the same slug under different branches.
    }

    public function down(): void
    {
        Schema::table('ecommerce_categories', function (Blueprint $table) {
            $table->foreignUuid('branch_id')->nullable(false)->change();
            $table->unique(['branch_id', 'slug']);
        });
    }
};

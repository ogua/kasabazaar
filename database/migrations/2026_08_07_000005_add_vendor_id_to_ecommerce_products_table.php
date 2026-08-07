<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->foreignUuid('vendor_id')->nullable()->after('branch_id')->constrained('vendors')->cascadeOnDelete();
        });

        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'slug']);
            $table->foreignUuid('branch_id')->nullable()->change();
            $table->unique(['vendor_id', 'slug']);
            $table->index(['vendor_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->dropIndex(['vendor_id', 'is_active']);
            $table->dropUnique(['vendor_id', 'slug']);
            $table->foreignUuid('branch_id')->nullable(false)->change();
            $table->unique(['branch_id', 'slug']);
        });

        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
        });
    }
};

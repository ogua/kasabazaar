<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->foreignUuid('order_group_id')->nullable()->after('user_id')->constrained('ecommerce_order_groups')->nullOnDelete();
            $table->foreignUuid('vendor_id')->nullable()->after('order_group_id')->constrained('vendors')->nullOnDelete();
            $table->decimal('tax_ghs', 10, 2)->default(0)->after('discount_ghs');
        });

        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->foreignUuid('branch_id')->nullable()->change();
        });

        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->index('order_group_id');
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropIndex(['vendor_id', 'status']);
            $table->dropIndex(['order_group_id']);
        });

        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->foreignUuid('branch_id')->nullable(false)->change();
        });

        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn('tax_ghs');
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropConstrainedForeignId('order_group_id');
        });
    }
};

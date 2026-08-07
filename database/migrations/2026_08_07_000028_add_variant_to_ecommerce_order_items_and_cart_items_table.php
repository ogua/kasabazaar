<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->foreignUuid('product_variant_id')->nullable()->after('ecommerce_product_id')->constrained('product_variants')->nullOnDelete();
            $table->string('variant_label')->nullable()->after('product_variant_id');
        });

        Schema::table('ecommerce_cart_items', function (Blueprint $table) {
            $table->foreignUuid('product_variant_id')->nullable()->after('vendor_id')->constrained('product_variants')->nullOnDelete();
        });

        // MySQL/InnoDB uses the (cart_id, ecommerce_product_id) unique index to
        // satisfy the FK requirement on cart_id, so the replacement index must
        // be added before the old one is dropped, not after.
        Schema::table('ecommerce_cart_items', function (Blueprint $table) {
            $table->unique(['cart_id', 'ecommerce_product_id', 'product_variant_id'], 'cart_item_product_variant_unique');
        });

        Schema::table('ecommerce_cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'ecommerce_product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_cart_items', function (Blueprint $table) {
            $table->unique(['cart_id', 'ecommerce_product_id']);
        });

        Schema::table('ecommerce_cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_item_product_variant_unique');
        });

        Schema::table('ecommerce_cart_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
        });

        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->dropColumn('variant_label');
            $table->dropConstrainedForeignId('product_variant_id');
        });
    }
};

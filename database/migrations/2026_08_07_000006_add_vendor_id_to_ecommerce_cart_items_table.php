<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_cart_items', function (Blueprint $table) {
            $table->foreignUuid('vendor_id')->nullable()->after('ecommerce_product_id')->constrained('vendors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_cart_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
        });
    }
};

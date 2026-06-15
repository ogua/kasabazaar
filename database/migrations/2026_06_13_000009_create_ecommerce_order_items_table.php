<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('ecommerce_orders')->cascadeOnDelete();
            // nullable so order history survives product deletion
            $table->foreignUuid('ecommerce_product_id')->nullable()->constrained('ecommerce_products')->nullOnDelete();
            $table->string('product_name'); // snapshot
            $table->string('product_sku')->nullable(); // snapshot
            $table->integer('quantity');
            $table->decimal('unit_price_ghs', 10, 2); // price at checkout time
            $table->decimal('total_ghs', 10, 2);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_order_items');
    }
};

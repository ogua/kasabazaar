<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->constrained('ecommerce_carts')->cascadeOnDelete();
            $table->foreignUuid('ecommerce_product_id')->constrained('ecommerce_products')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price_ghs', 10, 2); // snapshot at add time
            $table->timestamps();

            $table->unique(['cart_id', 'ecommerce_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_cart_items');
    }
};

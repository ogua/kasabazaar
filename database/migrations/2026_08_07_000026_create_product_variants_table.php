<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ecommerce_product_id')->constrained('ecommerce_products')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->decimal('price_ghs', 10, 2)->nullable();
            $table->decimal('price_usd', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->foreignUuid('image_id')->nullable()->constrained('ecommerce_product_images')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ecommerce_product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

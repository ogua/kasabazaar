<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('ecommerce_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->json('specifications')->nullable();
            $table->decimal('price_ghs', 10, 2);
            $table->decimal('price_usd', 10, 2)->nullable();
            $table->decimal('discount_price_ghs', 10, 2)->nullable();
            $table->decimal('discount_price_usd', 10, 2)->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'slug']);
            $table->index(['branch_id', 'is_active']);
            $table->index(['branch_id', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_products');
    }
};

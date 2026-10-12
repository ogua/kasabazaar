<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace storefront banners (homepage hero slider and promo slots).
 * Moved here from the kmarket storefront, which now reads them over
 * GET /api/v1/marketplace/banners instead of keeping its own table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('eyebrow')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('link_url')->nullable();
            $table->string('image_path');
            $table->string('position')->default('homepage_hero');
            $table->integer('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['position', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};

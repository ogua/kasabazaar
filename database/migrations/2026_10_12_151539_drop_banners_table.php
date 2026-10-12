<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront banners moved to the kasabazaar backend (banners table there,
 * managed under E-Commerce → Storefront Banners) and are read over
 * GET /api/v1/marketplace/banners. This storefront no longer stores them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('banners');
    }

    public function down(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_path');
            $table->string('link_url')->nullable();
            $table->string('position')->default('homepage_hero');
            $table->integer('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['position', 'is_active']);
        });
    }
};

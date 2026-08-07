<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shipping_zone_id')->nullable()->constrained('shipping_zones')->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('fee_ghs', 8, 2);
            $table->integer('min_days')->nullable();
            $table->integer('max_days')->nullable();
            $table->decimal('free_shipping_threshold_ghs', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['vendor_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};

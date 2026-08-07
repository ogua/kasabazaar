<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_attribute_value', function (Blueprint $table) {
            $table->uuid('product_variant_id');
            $table->uuid('product_attribute_value_id');

            $table->primary(['product_variant_id', 'product_attribute_value_id'], 'variant_attr_value_primary');

            $table->foreign('product_variant_id', 'pvav_variant_foreign')
                ->references('id')->on('product_variants')->cascadeOnDelete();
            $table->foreign('product_attribute_value_id', 'pvav_attribute_value_foreign')
                ->references('id')->on('product_attribute_values')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_value');
    }
};

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchaseditems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipment_id')->nullable();
            $table->string('name');
            $table->integer('quantity');
            $table->decimal('item_cost', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchaseditems');
    }
};

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
        Schema::create('schedule_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pickup_schedule_id');
            $table->string('product_id');
            $table->integer('quantity')->default(1);
            $table->decimal('weight', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_items');
    }
};

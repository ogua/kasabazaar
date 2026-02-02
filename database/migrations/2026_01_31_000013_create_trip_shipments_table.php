<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trip_shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('trip_id');
            $table->uuid('shipment_id');
            $table->enum('delivery_status', ['pending', 'delivered', 'failed', 'partial'])->default('pending');
            $table->text('delivery_notes')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('receiver_signature')->nullable();
            $table->timestamps();

            $table->foreign('trip_id')->references('id')->on('trips')->cascadeOnDelete();
            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            $table->unique(['trip_id', 'shipment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_shipments');
    }
};

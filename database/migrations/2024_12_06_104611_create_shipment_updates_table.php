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
        Schema::create('shipment_updates', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('shipment_id'); // Related shipment
            $table->string('location'); // Current location
            $table->string('status'); // Current status (e.g., "In Transit", "At Customs")
            $table->text('remarks')->nullable(); // Optional remarks
            $table->dateTime('updated_at'); // Time of the update

            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_updates');
    }
};

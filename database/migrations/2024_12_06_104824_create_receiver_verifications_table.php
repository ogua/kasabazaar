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
        Schema::create('receiver_verifications', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('shipment_id'); // Related shipment
            $table->string('receiver_name'); // Name of the receiver
            $table->string('receiver_id_type'); // Type of ID provided
            $table->string('receiver_id_number'); // ID number
            $table->dateTime('verified_at'); // Verification time
            $table->boolean('is_verified')->default(false); // Verification status
            $table->timestamps();

            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiver_verifications');
    }
};

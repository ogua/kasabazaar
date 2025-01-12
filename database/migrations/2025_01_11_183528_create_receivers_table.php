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
        Schema::create('receivers', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('shipment_id')->nullable();
            $table->string('receiver_name')->nullable(); // Receiver's full name
            $table->string('receiver_phone')->nullable(); // Receiver's phone number

            $table->string('receiver_email')->nullable()->nullable();
            $table->string('country')->nullable()->nullable();
            $table->string('state_region')->nullable()->nullable();
            $table->string('city')->nullable()->nullable();
            $table->text('address')->nullable()->nullable();

            $table->string('receiver_id_type')->nullable(); // Receiver's ID type
            $table->string('receiver_id_number')->nullable(); // Receiver's ID number
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receivers');
    }
};

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
        Schema::create('shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id'); // Sender
            $table->uuid('branch_id'); // Origin branch
            $table->string('receiver_name'); // Receiver's full name
            $table->string('receiver_phone'); // Receiver's phone number
            $table->string('receiver_id_type'); // Receiver's ID type
            $table->string('receiver_id_number'); // Receiver's ID number
            $table->string('tracking_number')->unique(); // Unique tracking number
            $table->uuid('origin_branch_id'); // Origin branch
            $table->uuid('destination_branch_id'); // Destination branch
            $table->enum('status', ['pending', 'in transit', 'delivered', 'cancelled']); // Shipment status
            $table->dateTime('shipped_at')->nullable(); // Shipment start time
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->dateTime('delivered_at')->nullable(); // Delivery time

            $table->timestamps();

             // Foreign key relationships
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('origin_branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('destination_branch_id')->references('id')->on('branches')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

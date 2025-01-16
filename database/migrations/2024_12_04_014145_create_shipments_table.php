<?php

use App\Models\Shipment;
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

            $table->string('shipping_reference')->nullable();
            $table->string('tracking_number')->nullable(); // Unique tracking number
            $table->string('origin_branch_id'); // Origin branch
            $table->string('destination_branch_id'); // Destination branch
            $table->enum('status', ['pending', 'in transit', 'delivered', 'cancelled']); // Shipment status
            $table->dateTime('shipped_at')->nullable(); // Shipment start time
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->dateTime('delivered_at')->nullable(); // Delivery time
            $table->uuid('recorderd_by')->nullable();
            $table->decimal('total',10,2)->default(0);
            $table->decimal('paid',10,2)->default(0);
            $table->enum('payment_status', ['pending', 'paid', 'partial'])->default('pending');

            $table->boolean('is_received')->default(false);
            $table->boolean('signed_received_form')->nullable();
            $table->boolean('is_diclaimer_aggred')->nullable();
            $table->boolean('is_agreement_aggred')->nullable();

            $table->timestamps();

             // Foreign key relationships
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
           // $table->foreign('origin_branch_id')->references('id')->on('branches')->onDelete('cascade');
           // $table->foreign('destination_branch_id')->references('id')->on('branches')->onDelete('cascade');

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

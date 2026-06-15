<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_order_shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->unique()->constrained('ecommerce_orders')->cascadeOnDelete();
            $table->string('tracking_number')->unique();
            $table->string('courier')->nullable();
            $table->foreignUuid('delivery_person_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->date('estimated_delivery')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_order_shipments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_shipment_tracking_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_shipment_id')->constrained('ecommerce_order_shipments')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('status')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['order_shipment_id', 'recorded_at'], 'ectl_shipment_recorded_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_shipment_tracking_logs');
    }
};

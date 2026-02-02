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
        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vehicle_id');
            $table->uuid('recorded_by');

            $table->string('maintenance_type');
            $table->text('description');
            $table->string('service_provider')->nullable();
            $table->decimal('cost', 10, 2);
            $table->integer('mileage_at_service')->nullable();
            $table->date('service_date');
            $table->date('next_service_date')->nullable();

            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenances');
    }
};

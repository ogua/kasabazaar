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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id');

            $table->string('registration_number')->unique();
            $table->string('vehicle_type');
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->year('year')->nullable();
            $table->string('color')->nullable();

            // Capacity
            $table->decimal('max_weight_kg', 10, 2)->nullable();
            $table->decimal('max_volume_cbm', 10, 2)->nullable();

            // Status
            $table->enum('status', ['available', 'in_use', 'maintenance', 'retired'])->default('available');

            // Insurance & Documentation
            $table->date('insurance_expiry')->nullable();
            $table->date('roadworthy_expiry')->nullable();
            $table->date('registration_expiry')->nullable();

            // Maintenance tracking
            $table->date('last_service_date')->nullable();
            $table->integer('last_service_mileage')->nullable();
            $table->integer('current_mileage')->nullable();
            $table->date('next_service_due')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

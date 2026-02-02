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
        Schema::create('trips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id');
            $table->uuid('vehicle_id');
            $table->uuid('driver_id');
            $table->uuid('assistant_id')->nullable();

            $table->string('trip_reference')->unique();

            // Route information
            $table->string('origin');
            $table->string('destination');
            $table->text('route_description')->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();

            // Timing
            $table->dateTime('scheduled_date')->nullable();
            $table->dateTime('scheduled_departure')->nullable();
            $table->dateTime('scheduled_arrival')->nullable();
            $table->dateTime('actual_departure')->nullable();
            $table->dateTime('actual_arrival')->nullable();

            // Status
            $table->string('status', 50)->default('scheduled');

            // Costs
            $table->decimal('fuel_cost', 10, 2)->default(0);
            $table->decimal('toll_fees', 10, 2)->default(0);
            $table->decimal('driver_allowance', 10, 2)->default(0);
            $table->decimal('other_costs', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);

            // Mileage
            $table->integer('start_mileage')->nullable();
            $table->integer('end_mileage')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('vehicle_id')->references('id')->on('vehicles');
            $table->foreign('driver_id')->references('id')->on('staff');
            $table->foreign('assistant_id')->references('id')->on('staff')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};

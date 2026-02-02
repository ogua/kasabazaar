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
        Schema::create('client_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id')->unique();

            $table->enum('payment_reliability', ['excellent', 'good', 'average', 'poor'])->default('good');
            $table->enum('shipment_frequency', ['high', 'medium', 'low', 'inactive'])->default('medium');
            $table->decimal('total_lifetime_value', 15, 2)->default(0);
            $table->integer('total_shipments')->default(0);
            $table->boolean('is_vip')->default(false);
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_ratings');
    }
};

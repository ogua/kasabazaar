<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('submitted');
            $table->json('receivers');
            $table->string('pickup_location');
            $table->dateTime('preferred_pickup_at');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignUuid('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_requests');
    }
};

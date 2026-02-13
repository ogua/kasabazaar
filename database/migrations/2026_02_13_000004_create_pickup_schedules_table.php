<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('pickup_location');
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, confirmed, in-progress, completed, cancelled
            $table->text('notes')->nullable();
            $table->text('items_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_schedules');
    }
};

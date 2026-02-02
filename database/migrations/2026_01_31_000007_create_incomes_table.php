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
        Schema::create('incomes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('income_category_id');
            $table->uuid('branch_id');
            $table->uuid('recorded_by');
            $table->uuid('shipment_id')->nullable();

            // Income details
            $table->string('reference')->unique();
            $table->string('title');
            $table->text('description')->nullable();

            // Amount in original currency (USD)
            $table->decimal('amount_usd', 12, 2);
            $table->decimal('exchange_rate', 10, 4);
            $table->decimal('amount_ghs', 12, 2);

            // Source information
            $table->string('source_name')->nullable();
            $table->string('source_contact')->nullable();

            // Timing
            $table->date('income_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'card', 'other'])->default('cash');
            $table->string('payment_reference')->nullable();

            // Documentation
            $table->string('receipt_path')->nullable();

            // Status
            $table->enum('status', ['pending', 'received', 'cancelled'])->default('received');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('income_category_id')->references('id')->on('income_categories');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('recorded_by')->references('id')->on('users');
            $table->foreign('shipment_id')->references('id')->on('shipments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};

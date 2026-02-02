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
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipment_id');
            $table->uuid('expense_category_id');
            $table->uuid('branch_id');
            $table->uuid('recorded_by');

            // Expense details
            $table->string('reference')->unique();
            $table->string('title');
            $table->text('description')->nullable();

            // Amount in original currency (USD)
            $table->decimal('amount_usd', 12, 2);
            $table->decimal('exchange_rate', 10, 4);
            $table->decimal('amount_ghs', 12, 2);

            // Timing
            $table->date('expense_date');
            $table->enum('expense_stage', ['pre_shipment', 'during_shipment', 'post_shipment']);

            // Documentation
            $table->string('receipt_path')->nullable();
            $table->string('vendor_name')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            $table->foreign('expense_category_id')->references('id')->on('expense_categories');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('recorded_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('investor_id');
            $table->string('reference')->unique();
            $table->decimal('principal_amount', 14, 2);
            $table->decimal('exchange_rate_at_investment', 10, 4)->nullable();
            $table->decimal('principal_amount_ghs', 14, 2)->nullable();
            $table->date('start_date');
            $table->string('status')->default('pending_payment');
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->unsignedSmallInteger('last_interest_posted_year')->nullable();

            // Deposit / money-in recording
            $table->string('deposit_gateway')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('receipt_path')->nullable();

            $table->uuid('recorded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('investor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};

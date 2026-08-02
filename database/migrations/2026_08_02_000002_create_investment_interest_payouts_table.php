<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_interest_payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('investment_id');
            $table->uuid('investor_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date');
            $table->decimal('principal_balance', 14, 2);
            $table->decimal('rate_applied', 5, 2);
            $table->decimal('amount', 14, 2);
            $table->string('status')->default('due');
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->date('paid_at')->nullable();

            // Payout / money-out recording — same shape as investment_withdrawal_requests
            $table->string('payout_gateway')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('paystack_transfer_code')->nullable();

            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('investment_id');
            $table->index('investor_id');
            $table->index(['status', 'due_date']);
            $table->unique(['investment_id', 'period_start', 'period_end'], 'investment_interest_payouts_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_interest_payouts');
    }
};

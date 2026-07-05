<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_withdrawal_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('investment_id');
            $table->uuid('investor_id');
            $table->boolean('is_full_withdrawal')->default(false);
            $table->decimal('requested_amount', 14, 2)->nullable();
            $table->date('notice_date');
            $table->date('required_action_by');
            $table->date('payment_due_by')->nullable();
            $table->string('status')->default('submitted');
            $table->decimal('remaining_balance_after', 14, 2)->nullable();
            $table->boolean('exception_approved')->default(false);
            $table->unsignedSmallInteger('deferral_days')->nullable();
            $table->text('deferral_reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->date('paid_at')->nullable();

            // Payout / money-out recording
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_withdrawal_requests');
    }
};

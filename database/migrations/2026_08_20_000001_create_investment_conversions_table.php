<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_conversions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('investor_id');
            $table->string('reference')->unique();
            $table->string('direction'); // InvestmentConversionDirection enum value
            $table->date('conversion_date');

            // Set once the conversion executes and the successor tranche exists.
            $table->uuid('target_investment_id')->nullable();

            $table->decimal('total_principal_rolled', 14, 2)->default(0);
            $table->decimal('total_interest_rolled', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);

            $table->string('status')->default('pending_approval');
            $table->boolean('requested_by_investor')->default(false);

            // Staff overrides for the two guards execute() enforces — mirrors
            // exception_approved on investment_withdrawal_requests.
            $table->boolean('maturity_exception_approved')->default(false);
            $table->boolean('threshold_exception_approved')->default(false);

            // Terms the successor tranche is issued under. payout_frequency is
            // required when direction is to_loan; target_annual_rate is pinned as a
            // rate override on the new tranche so a loan cannot silently re-price.
            $table->unsignedSmallInteger('target_contract_term_months')->nullable();
            $table->string('target_payout_frequency')->nullable();
            $table->decimal('target_annual_rate', 5, 2)->nullable();

            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->uuid('executed_by')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('investor_id');
            $table->index('target_investment_id');
            $table->index(['status', 'conversion_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_conversions');
    }
};

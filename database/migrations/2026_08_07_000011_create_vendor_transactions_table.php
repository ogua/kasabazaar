<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignUuid('ecommerce_order_id')->nullable()->constrained('ecommerce_orders')->nullOnDelete();
            $table->string('type'); // sale_credit, commission_fee, payout, refund_reversal, adjustment
            $table->decimal('amount_ghs', 12, 2); // signed: positive = credit, negative = debit
            $table->decimal('balance_after_ghs', 12, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_transactions');
    }
};

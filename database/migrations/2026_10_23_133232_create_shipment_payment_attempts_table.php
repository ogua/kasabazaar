<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per Paystack checkout started for a shipment. The reference is ours
     * (random, unguessable), so it doubles as the key a payer uses to verify a payment.
     */
    public function up(): void
    {
        Schema::create('shipment_payment_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipment_id')->index();
            $table->uuid('payment_id')->nullable()->index();
            $table->string('reference')->unique();
            $table->string('source', 30);
            $table->decimal('amount_usd', 10, 2);
            $table->string('currency', 3);
            $table->unsignedBigInteger('amount_subunit');
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->string('payer_email')->nullable();
            $table->string('payer_phone', 30)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('channel', 30)->nullable();
            $table->string('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_payment_attempts');
    }
};

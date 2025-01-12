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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('branch_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('shipment_id')->nullable();
            $table->uuid('account_id')->nullable();
            $table->enum('payment_type',['debit','credit'])->nullable("debit");
            $table->string('payment_ref')->nullable();
            $table->string('paying_type')->nullable();
            $table->text('description')->nullable();
            $table->decimal('balance',10,2)->default(0);
            $table->decimal('amount',10,2)->nullable();
            $table->decimal('change',10,2)->nullable();
            $table->string('cheque_no')->nullable();
            $table->string('customer_stripe_id')->nullable();
            $table->string('charge_id')->nullable();
            $table->string('paypal_transaction_id')->nullable();
            $table->string('paying_method')->nullable();
            $table->text('payment_note')->nullable();
            $table->string('bankname')->nullable();
            $table->datetime('paid_on')->nullable();
            $table->string('accountnumber')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

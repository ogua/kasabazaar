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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('client_fullname')->nullable();
            $table->string('transaction_id');
            $table->string('transaction_status');
            $table->string('reference');
            $table->string('phone')->nullable();
            $table->string('shipment_reference')->nullable();
            $table->uuid('shipment_id')->nullable();
            $table->string('amount');
            $table->text('message')->nullable();
            $table->text('reponse');
            $table->dateTime('payment_date');
            $table->string('channel');
            $table->string('currency');
            $table->string('ipaddress');
            $table->string('fee_charge');
            $table->string('authcode')->nullable();
            $table->string('card_type')->nullable();
            $table->string('bank');
            $table->string('countrycode');
            $table->string('brand');
            $table->string('mobile_money_number');
            $table->string('full_name');
            $table->string('code');
            $table->string('email');
            $table->string('log_start_time');
            $table->string('log_spent_time');
            $table->string('log_attempts');
            $table->text('log_errors');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

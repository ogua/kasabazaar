<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Receipts staff issue by hand for money received before the system existed (or otherwise
 * outside it). A manual receipt is a document only: it never creates a Payment, Income or
 * cashbook entry and nothing in the accounts reads this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id')->index();
            $table->uuid('client_id')->index();
            $table->string('receipt_number')->unique();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method');
            $table->date('paid_on');
            $table->string('description');
            $table->string('related_reference')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->string('received_by')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('issued_by')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->string('last_sent_to')->nullable();
            $table->unsignedInteger('times_sent')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_receipts');
    }
};

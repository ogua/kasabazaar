<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('investment_id');
            $table->uuid('investor_id');
            $table->date('date');
            $table->string('type');
            $table->decimal('op_balance', 14, 2)->default(0);
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->decimal('cl_balance', 14, 2)->default(0); // auto-computed
            $table->decimal('exchange_rate_at_transaction', 10, 4)->nullable();
            $table->decimal('amount_ghs', 14, 2)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->boolean('posted')->default(true);
            $table->uuid('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->text('description')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('investment_id');
            $table->index('investor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_transactions');
    }
};

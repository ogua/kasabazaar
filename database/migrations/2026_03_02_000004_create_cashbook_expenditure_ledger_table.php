<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashbook_expenditure_ledger', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->tinyInteger('month'); // 1–12
            $table->smallInteger('year');
            $table->string('ledger_type'); // ExpenditureLedgerType enum value
            $table->string('particulars')->default('Cash Book Payment');
            $table->decimal('op_balance', 12, 2)->default(0);
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->decimal('cl_balance', 12, 2)->default(0); // auto-computed
            $table->timestamps();

            $table->unique(['month', 'year', 'ledger_type']);
            $table->index(['month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbook_expenditure_ledger');
    }
};

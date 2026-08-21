<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('year')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('open'); // FiscalPeriodStatus
            $table->string('source')->default('derived'); // FiscalPeriodSource

            // Rate used to translate GHS-denominated balances (the cashbook is kept in
            // GHS while the statements present in USD) where no per-row rate exists.
            $table->decimal('closing_exchange_rate', 10, 4)->nullable();

            $table->uuid('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};

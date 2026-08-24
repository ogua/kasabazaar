<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A recorded bank/mobile-money balance at a point in time.
     *
     * Nothing else in the system knows what is in the bank: the cashbook is unused and
     * shipments only record what was invoiced and received, not the resulting balance.
     * Without this the balance sheet carries receivables and no cash, and cannot be
     * made to balance for any year after the last keyed one.
     *
     * Deliberately a stated balance rather than a derived one — it is checked against
     * a bank statement, so it is verifiable. Deriving it from payments less expenses
     * would produce a confident figure that silently drifts from reality.
     */
    public function up(): void
    {
        Schema::create('cash_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('as_of_date');
            $table->decimal('bank_balance', 16, 2)->default(0);
            $table->decimal('momo_balance', 16, 2)->default(0);
            $table->string('currency', 3)->default('GHS');

            // Rate at the statement date, so a position recorded in Cedis converts at
            // the rate that applied then rather than being restated later.
            $table->decimal('exchange_rate', 10, 4)->nullable();

            $table->uuid('recorded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('as_of_date');
            $table->index('as_of_date', 'cash_positions_as_of_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_positions');
    }
};

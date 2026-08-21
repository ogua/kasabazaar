<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('fiscal_year');
            $table->uuid('chart_of_account_id');

            $table->decimal('opening_balance', 16, 2)->default(0);
            $table->decimal('movement', 16, 2)->default(0);
            $table->decimal('closing_balance', 16, 2)->default(0);

            $table->uuid('entered_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year', 'chart_of_account_id'], 'account_balances_year_account_unique');
            $table->index('fiscal_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balances');
    }
};

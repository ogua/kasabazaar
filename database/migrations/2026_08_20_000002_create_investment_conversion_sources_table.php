<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_conversion_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('investment_conversion_id');
            $table->uuid('source_investment_id');
            $table->string('mode'); // InvestmentConversionSourceMode enum value

            // Settlement snapshot, taken at execution time. Stored rather than
            // recomputed so a statement or agreement rendered years later shows the
            // figures the parties actually agreed, not what today's rates would give.
            $table->decimal('principal_at_conversion', 14, 2)->default(0);
            $table->decimal('interest_at_conversion', 14, 2)->default(0);
            $table->decimal('amount_rolled', 14, 2)->default(0);

            // Interest the investor took as cash instead of rolling (principal_only mode).
            $table->decimal('amount_paid_out', 14, 2)->default(0);

            $table->decimal('remaining_balance_after', 14, 2)->default(0);
            $table->boolean('source_fully_closed')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('investment_conversion_id');
            $table->index('source_investment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_conversion_sources');
    }
};

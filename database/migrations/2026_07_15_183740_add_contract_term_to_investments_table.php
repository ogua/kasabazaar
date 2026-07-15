<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->unsignedSmallInteger('contract_term_months')->default(12)->after('start_date');
            $table->date('maturity_date')->nullable()->after('contract_term_months');
        });

        // Backfill existing rows so the maturity gate reflects each investment's
        // actual start date rather than the migration run date.
        DB::table('investments')->orderBy('id')->chunk(100, function ($investments) {
            foreach ($investments as $investment) {
                DB::table('investments')
                    ->where('id', $investment->id)
                    ->update([
                        'maturity_date' => \Carbon\Carbon::parse($investment->start_date)
                            ->addMonths($investment->contract_term_months)
                            ->toDateString(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn(['contract_term_months', 'maturity_date']);
        });
    }
};

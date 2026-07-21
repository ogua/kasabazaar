<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_transactions', function (Blueprint $table) {
            $table->date('period_start')->nullable()->after('date');
            $table->date('period_end')->nullable()->after('period_start');
            $table->decimal('rate_applied', 5, 2)->nullable()->after('cl_balance');
            $table->uuid('edited_by')->nullable()->after('posted_by');
            $table->timestamp('edited_at')->nullable()->after('posted_at');
        });

        // Backfill existing interest_credit rows so period_start/period_end reflect
        // the calendar year they were originally posted for, not left null.
        DB::table('investment_transactions')
            ->where('type', 'interest_credit')
            ->whereNotNull('year')
            ->whereNull('period_end')
            ->orderBy('id')
            ->chunkById(100, function ($transactions) {
                foreach ($transactions as $transaction) {
                    $investment = DB::table('investments')->where('id', $transaction->investment_id)->first();

                    if (! $investment) {
                        continue;
                    }

                    $yearStart = \Carbon\Carbon::create((int) $transaction->year, 1, 1);
                    $investmentStart = \Carbon\Carbon::parse($investment->start_date);
                    $periodStart = $yearStart->greaterThan($investmentStart) ? $yearStart : $investmentStart;

                    DB::table('investment_transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'period_start' => $periodStart->toDateString(),
                            'period_end' => \Carbon\Carbon::create((int) $transaction->year, 12, 31)->toDateString(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('investment_transactions', function (Blueprint $table) {
            $table->dropColumn(['period_start', 'period_end', 'rate_applied', 'edited_by', 'edited_at']);
        });
    }
};

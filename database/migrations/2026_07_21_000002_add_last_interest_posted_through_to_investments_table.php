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
            $table->date('last_interest_posted_through')->nullable()->after('last_interest_posted_year');
        });

        DB::table('investments')
            ->whereNotNull('last_interest_posted_year')
            ->orderBy('id')
            ->chunkById(100, function ($investments) {
                foreach ($investments as $investment) {
                    DB::table('investments')
                        ->where('id', $investment->id)
                        ->update([
                            'last_interest_posted_through' => \Carbon\Carbon::create(
                                (int) $investment->last_interest_posted_year,
                                12,
                                31
                            )->toDateString(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn('last_interest_posted_through');
        });
    }
};

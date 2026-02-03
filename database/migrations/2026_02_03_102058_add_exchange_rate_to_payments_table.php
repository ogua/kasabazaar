<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add currency tracking columns
            $table->string('currency', 3)->default('USD')->after('amount');
            $table->decimal('amount_usd', 10, 2)->nullable()->after('currency');
            $table->decimal('exchange_rate', 10, 4)->nullable()->after('amount_usd');
            $table->decimal('amount_ghs', 10, 2)->nullable()->after('exchange_rate');
        });

        // Migrate existing data: treat existing 'amount' as USD
        DB::table('payments')->update([
            'amount_usd' => DB::raw('amount'),
            'currency' => 'USD'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['currency', 'amount_usd', 'exchange_rate', 'amount_ghs']);
        });
    }
};

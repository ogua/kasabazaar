<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets each expense/income category say which statement line it belongs to,
     * instead of the statement engine hardcoding category names. Nullable: an
     * unmapped category still reports, it just falls into the catch-all account.
     */
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->uuid('chart_of_account_id')->nullable()->after('code');
            $table->index('chart_of_account_id');
        });

        Schema::table('income_categories', function (Blueprint $table) {
            $table->uuid('chart_of_account_id')->nullable()->after('code');
            $table->index('chart_of_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropIndex(['chart_of_account_id']);
            $table->dropColumn('chart_of_account_id');
        });

        Schema::table('income_categories', function (Blueprint $table) {
            $table->dropIndex(['chart_of_account_id']);
            $table->dropColumn('chart_of_account_id');
        });
    }
};

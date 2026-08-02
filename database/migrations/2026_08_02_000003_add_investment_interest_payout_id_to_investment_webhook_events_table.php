<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_webhook_events', function (Blueprint $table) {
            $table->uuid('investment_interest_payout_id')->nullable()->after('investment_withdrawal_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('investment_webhook_events', function (Blueprint $table) {
            $table->dropColumn('investment_interest_payout_id');
        });
    }
};

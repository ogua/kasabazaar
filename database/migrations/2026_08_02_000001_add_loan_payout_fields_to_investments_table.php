<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->string('payout_frequency')->nullable()->after('agreement_finalized_by');
            $table->date('next_payout_due_date')->nullable()->after('payout_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn(['payout_frequency', 'next_payout_due_date']);
        });
    }
};

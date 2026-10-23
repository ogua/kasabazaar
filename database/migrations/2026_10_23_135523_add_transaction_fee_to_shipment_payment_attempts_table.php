<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Paystack transaction charge the payer covers on top of the amount credited
     * to the shipment. The rate is stored per attempt so a receipt keeps showing the
     * rate that was actually charged after the configured rate changes.
     */
    public function up(): void
    {
        Schema::table('shipment_payment_attempts', function (Blueprint $table) {
            $table->decimal('fee_percent', 5, 2)->default(0)->after('amount_usd');
            $table->decimal('fee_usd', 10, 2)->default(0)->after('fee_percent');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_payment_attempts', function (Blueprint $table) {
            $table->dropColumn(['fee_percent', 'fee_usd']);
        });
    }
};

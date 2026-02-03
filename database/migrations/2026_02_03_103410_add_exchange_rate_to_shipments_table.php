<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Add exchange rate snapshot at shipment creation time
            $table->decimal('exchange_rate_at_shipment', 10, 4)->nullable()->after('total');
            $table->decimal('total_ghs', 10, 2)->nullable()->after('exchange_rate_at_shipment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate_at_shipment', 'total_ghs']);
        });
    }
};

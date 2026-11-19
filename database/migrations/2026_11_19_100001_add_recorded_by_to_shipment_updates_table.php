<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who moved the shipment to each stage. Internal only — the public tracking page never reads it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_updates', function (Blueprint $table) {
            $table->uuid('recorded_by')->nullable()->after('remarks');
            $table->index('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_updates', function (Blueprint $table) {
            $table->dropIndex(['recorded_by']);
            $table->dropColumn('recorded_by');
        });
    }
};

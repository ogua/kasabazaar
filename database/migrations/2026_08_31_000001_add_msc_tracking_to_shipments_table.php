<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('msc_tracking_number')->nullable()->after('tracking_number');
            $table->timestamp('msc_tracking_updated_at')->nullable()->after('msc_tracking_number');
            $table->index('msc_tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['msc_tracking_number']);
            $table->dropColumn(['msc_tracking_number', 'msc_tracking_updated_at']);
        });
    }
};

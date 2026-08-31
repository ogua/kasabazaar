<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->boolean('is_vehicle')->default(false)->after('item_cost');
            $table->string('vin')->nullable()->after('is_vehicle');
            $table->string('vehicle_make')->nullable()->after('vin');
            $table->string('vehicle_model')->nullable()->after('vehicle_make');
            $table->string('vehicle_year', 4)->nullable()->after('vehicle_model');
            $table->index('is_vehicle');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropIndex(['is_vehicle']);
            $table->dropColumn(['is_vehicle', 'vin', 'vehicle_make', 'vehicle_model', 'vehicle_year']);
        });
    }
};

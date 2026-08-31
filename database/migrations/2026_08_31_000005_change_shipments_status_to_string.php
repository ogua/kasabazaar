<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `shipments.status` was created as an enum of ['pending','in transit',
 * 'delivered','cancelled'] but the ShippingStatus PHP enum uses
 * pickup/shipped/cleared. Production drifted the column outside migrations;
 * this makes it a plain string everywhere so every ShippingStatus case is valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        // Normalise any legacy value the PHP enum can't represent.
        \App\Models\Shipment::withoutGlobalScopes()
            ->where('status', 'in transit')
            ->update(['status' => 'shipped']);
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }
};

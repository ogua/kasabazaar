<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->index(['branch_id', 'status'],   'shipments_branch_status_idx');
            $table->index('tracking_number',          'shipments_tracking_number_idx');
            $table->index('shipping_reference',       'shipments_shipping_reference_idx');
            $table->index(['client_id', 'status'],    'shipments_client_status_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['shipment_id', 'branch_id'], 'payments_shipment_branch_idx');
        });

        Schema::table('pickup_schedules', function (Blueprint $table) {
            $table->index(['client_id', 'status'],  'pickups_client_status_idx');
            $table->index(['branch_id', 'status'],  'pickups_branch_status_idx');
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'trips_branch_status_idx');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->index('branch_id', 'clients_branch_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('shipments_branch_status_idx');
            $table->dropIndex('shipments_tracking_number_idx');
            $table->dropIndex('shipments_shipping_reference_idx');
            $table->dropIndex('shipments_client_status_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_shipment_branch_idx');
        });

        Schema::table('pickup_schedules', function (Blueprint $table) {
            $table->dropIndex('pickups_client_status_idx');
            $table->dropIndex('pickups_branch_status_idx');
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex('trips_branch_status_idx');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_branch_id_idx');
        });
    }
};

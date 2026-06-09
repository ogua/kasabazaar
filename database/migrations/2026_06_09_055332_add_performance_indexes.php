<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!$this->hasIndex('shipments', 'shipments_branch_id_status_index')) {
                $table->index(['branch_id', 'status']);
            }
            if (!$this->hasIndex('shipments', 'shipments_tracking_number_index')) {
                $table->index('tracking_number');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!$this->hasIndex('payments', 'payments_shipment_id_branch_id_index')) {
                $table->index(['shipment_id', 'branch_id']);
            }
        });

        Schema::table('pickup_schedules', function (Blueprint $table) {
            if (!$this->hasIndex('pickup_schedules', 'pickup_schedules_client_id_status_index')) {
                $table->index(['client_id', 'status']);
            }
            if (!$this->hasIndex('pickup_schedules', 'pickup_schedules_branch_id_status_index')) {
                $table->index(['branch_id', 'status']);
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (!$this->hasIndex('clients', 'clients_branch_id_index')) {
                $table->index('branch_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'status']);
            $table->dropIndex(['tracking_number']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['shipment_id', 'branch_id']);
        });

        Schema::table('pickup_schedules', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'status']);
            $table->dropIndex(['branch_id', 'status']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return collect(\Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->contains($indexName);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ties the pickup booked for a shipment request back to that request, so editing,
 * cancelling or rejecting the request keeps its pickup in step.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_schedules', function (Blueprint $table) {
            $table->foreignUuid('shipment_request_id')->nullable()->after('shipment_id')
                ->constrained('shipment_requests')->nullOnDelete();
        });

        // Client cancellations used to be stored as rejections; they now have their own status.
        DB::table('shipment_requests')
            ->where('status', 'rejected')
            ->where('rejection_reason', 'Cancelled by client')
            ->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        DB::table('shipment_requests')->where('status', 'cancelled')
            ->update(['status' => 'rejected', 'rejection_reason' => 'Cancelled by client']);

        Schema::table('pickup_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipment_request_id');
        });
    }
};

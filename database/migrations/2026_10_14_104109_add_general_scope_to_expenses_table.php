<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * General (overhead) expenses — rent, utilities, internet — are tied to neither a
 * shipment nor a container, so they have no shipping stage either. The stage column
 * becomes a nullable string (the old enum also rejected the "delivery" stage), and
 * rows already saved with no shipment and no container are relabelled "general".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('expense_stage')->nullable()->change();
        });

        DB::table('expenses')
            ->whereNull('shipment_id')
            ->whereNull('container_number')
            ->update(['expense_for' => 'general', 'expense_stage' => null]);
    }

    public function down(): void
    {
        DB::table('expenses')
            ->where('expense_for', 'general')
            ->update(['expense_for' => 'shipment']);

        DB::table('expenses')
            ->whereNull('expense_stage')
            ->orWhere('expense_stage', 'delivery')
            ->update(['expense_stage' => 'post_shipment']);

        Schema::table('expenses', function (Blueprint $table) {
            $table->enum('expense_stage', ['pre_shipment', 'during_shipment', 'post_shipment'])->change();
        });
    }
};

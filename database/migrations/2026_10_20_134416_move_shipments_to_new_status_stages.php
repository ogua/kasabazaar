<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * October 2026 stage list: Pickup → Warehouse → Loaded → Shipped → In Transit →
 * Harbour / Port GH → Sorting → Delivery → Received.
 *
 * `cleared` (customs cleared) is folded into `port`, and delivered shipments whose
 * receiver already confirmed receipt move to the new `received` stage. Status-history
 * rows keep their original values; the public tracking page still labels `cleared`.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('shipments')->where('status', 'cleared')->update(['status' => 'port']);

        DB::table('shipments')
            ->where('status', 'delivered')
            ->where('is_received', true)
            ->update(['status' => 'received']);
    }

    public function down(): void
    {
        DB::table('shipments')->where('status', 'received')->update(['status' => 'delivered']);
        DB::table('shipments')->whereIn('status', ['port', 'sorting'])->update(['status' => 'cleared']);
        DB::table('shipments')->whereIn('status', ['warehouse', 'loaded'])->update(['status' => 'pickup']);
        DB::table('shipments')->where('status', 'in_transit')->update(['status' => 'shipped']);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Until ShipmentObserver started writing status history on every status change,
 * only the "Update Status" action did — so shipments moved by Record Delivery, the
 * edit form, the mobile API, trips or bulk container jobs show a stale last entry
 * (typically "Shipment created - Pickup") on the public tracking page.
 *
 * For each shipment whose latest history row doesn't match its current status, add
 * one row for the current status, dated when we best know it happened. Also add a
 * "received" row where receipt is already confirmed, and fill delivered_at on
 * delivered shipments that never got one.
 */
return new class extends Migration
{
    private const LABELS = [
        'pending' => 'Shipment Received',
        'pickup' => 'Out for Pickup',
        'shipped' => 'In Transit',
        'cleared' => 'Customs Cleared',
        'delivered' => 'Delivered',
        'cancelled' => 'Shipment Cancelled',
    ];

    public function up(): void
    {
        DB::table('shipments')
            ->where('status', 'delivered')
            ->whereNull('delivered_at')
            ->update(['delivered_at' => DB::raw('updated_at')]);

        $shipments = DB::table('shipments as s')
            ->leftJoin('branches as ob', 'ob.id', '=', 's.origin_branch_id')
            ->leftJoin('branches as db', 'db.id', '=', 's.destination_branch_id')
            ->select('s.id', 's.status', 's.is_received', 's.delivered_at', 's.updated_at',
                // origin/destination_branch_id normally hold a place name, not a branch id.
                DB::raw('COALESCE(ob.name, s.origin_branch_id) as origin_name'),
                DB::raw('COALESCE(db.name, s.destination_branch_id) as destination_name'))
            ->get();

        foreach ($shipments as $s) {
            $history = DB::table('shipment_updates')
                ->where('shipment_id', $s->id)
                ->orderByDesc('updated_at')
                ->pluck('status')
                ->map(fn ($status) => strtolower((string) $status));

            $atDestination = in_array($s->status, ['cleared', 'delivered'], true);

            if ($history->first() !== $s->status && ! ($s->is_received && $history->first() === 'received')) {
                DB::table('shipment_updates')->insert([
                    'id' => (string) Str::ulid(),
                    'shipment_id' => $s->id,
                    'location' => ($atDestination ? $s->destination_name : $s->origin_name) ?? '',
                    'status' => $s->status,
                    'remarks' => self::LABELS[$s->status] ?? 'Status updated',
                    'updated_at' => $s->status === 'delivered' && $s->delivered_at ? $s->delivered_at : $s->updated_at,
                ]);
            }

            if ($s->is_received && ! $history->contains('received')) {
                DB::table('shipment_updates')->insert([
                    'id' => (string) Str::ulid(),
                    'shipment_id' => $s->id,
                    'location' => $s->destination_name ?? '',
                    'status' => 'received',
                    'remarks' => 'Receiver confirmed receipt',
                    // A second after the delivery row, so it always sorts as the latest entry.
                    'updated_at' => \Illuminate\Support\Carbon::parse($s->delivered_at ?? $s->updated_at)->addSecond(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Data backfill: the rows it adds are indistinguishable from history written
        // by ShipmentObserver afterwards, so they are deliberately left in place.
    }
};

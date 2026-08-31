<?php

namespace App\Console\Commands;

use App\Models\ShipmentItem;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class FlagVehicleShipmentItems extends Command
{
    protected $signature = 'app:flag-vehicle-shipment-items
        {--category=* : Product category name(s) to treat as a vehicle (default: Vehicles)}
        {--name=* : Also match items whose product name contains this text (repeatable)}
        {--dry-run : Report what would change without writing}';

    protected $description = 'Backfill shipment_items.is_vehicle = true for items whose product looks like a vehicle';

    public function handle(): int
    {
        $categories = $this->option('category') ?: ['Vehicles'];
        $categories = array_map('strtolower', array_map('trim', $categories));
        $names = array_filter(array_map('trim', $this->option('name')));

        $query = ShipmentItem::query()
            ->where('is_vehicle', false)
            ->whereHas('product', function (Builder $q) use ($categories, $names) {
                $q->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(category)'), $categories);

                foreach ($names as $name) {
                    $q->orWhere('name', 'like', '%'.$name.'%');
                }
            });

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No shipment items to flag (categories: '.implode(', ', $categories).').');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("[dry-run] {$total} shipment item(s) would be flagged as vehicles.");

            (clone $query)->with('product:id,name,category', 'shipment:id,shipping_reference')
                ->limit(20)
                ->get()
                ->each(fn (ShipmentItem $item) => $this->line(
                    "  - {$item->shipment?->shipping_reference} | {$item->product?->name} ({$item->product?->category})"
                ));

            if ($total > 20) {
                $this->line('  … and '.($total - 20).' more.');
            }

            return self::SUCCESS;
        }

        $updated = 0;
        $query->chunkById(500, function ($items) use (&$updated) {
            $ids = $items->pluck('id');
            $updated += ShipmentItem::whereIn('id', $ids)->update(['is_vehicle' => true]);
        });

        $this->info("Flagged {$updated} shipment item(s) as vehicles.");

        return self::SUCCESS;
    }
}

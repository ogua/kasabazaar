<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentDelivery;
use App\Models\ShipmentItem;
use App\Models\ShipmentUpdate;
use App\Models\Tracking;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The public tracking page's view of a shipment (resources/views/site/tracking.blade.php).
 *
 * Anyone holding a tracking number can see this, so it exposes only what such a person is
 * entitled to: the reference, the status, the branch names and the dates — plus, only when
 * the lookup was by the random tracking number (not the sequential, guessable shipping
 * reference), item names and masked receiver names. No client records, addresses, phone
 * numbers, VINs, values, costs or payment state.
 */
class PublicShipmentTracking
{
    /**
     * The public stages, in order, with their images under public/site/images/tracking.
     *
     * @var array<int, array{label: string, image: string}>
     */
    public const STAGES = [
        1 => ['label' => 'Pickup', 'image' => 'images/tracking/stage-picked.svg'],
        2 => ['label' => 'Warehouse', 'image' => 'images/tracking/stage-warehouse.svg'],
        3 => ['label' => 'Loaded', 'image' => 'images/tracking/stage-loaded.svg'],
        4 => ['label' => 'Shipped', 'image' => 'images/tracking/stage-shipped.svg'],
        5 => ['label' => 'In Transit', 'image' => 'images/tracking/stage-in-transit.svg'],
        6 => ['label' => 'Harbour / Port GH', 'image' => 'images/tracking/stage-port.svg'],
        7 => ['label' => 'Sorting', 'image' => 'images/tracking/stage-sorting.svg'],
        8 => ['label' => 'Delivery', 'image' => 'images/tracking/stage-delivery.svg'],
        9 => ['label' => 'Received', 'image' => 'images/tracking/stage-received.svg'],
    ];

    public const STAGE_SHIPPED = 4;

    public const STAGE_DELIVERY = 8;

    /**
     * The shipment with this tracking number or shipping reference, or null.
     */
    public function find(string $query): ?Shipment
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        return Shipment::query()
            ->with(['originBranch:id,name,country', 'destinationBranch:id,name,country'])
            ->where('tracking_number', $query)
            ->orWhere('shipping_reference', $query)
            ->first();
    }

    /**
     * Whether the lookup used the random tracking number, which alone unlocks the item list.
     * The shipping reference (CON50-26-C1-1, CON50-26-C1-2, ...) is sequential, so anyone
     * could walk through it — it gets the status alone.
     */
    public function lookedUpByTrackingNumber(Shipment $shipment, string $query): bool
    {
        return filled($shipment->tracking_number) && strcasecmp(trim($query), $shipment->tracking_number) === 0;
    }

    /**
     * The stored status string. Read raw so legacy values the enum no longer knows
     * ("in transit", "cleared") still display instead of failing the cast.
     */
    public function rawStatus(Shipment $shipment): string
    {
        return (string) $shipment->getRawOriginal('status');
    }

    /**
     * Where the origin / destination is, by name. The columns normally hold a place name picked
     * in the back office (Michigan, Illinois, ... Ghana) rather than a branch id.
     */
    public function placeName(Shipment $shipment, string $side): ?string
    {
        $branch = $side === 'origin' ? $shipment->originBranch : $shipment->destinationBranch;
        $raw = $side === 'origin' ? $shipment->getRawOriginal('origin_branch_id') : $shipment->getRawOriginal('destination_branch_id');

        return $branch?->name ?? (filled($raw) ? (string) $raw : null);
    }

    /**
     * The shipment's status history, newest first. Both `trackings` (branch-scoped status
     * changes) and `shipment_updates` (free-text locations) record movement; neither is
     * authoritative on its own, so both are read.
     *
     * @return list<array{status: string, note: ?string, location: string, happened_at: ?CarbonInterface}>
     */
    public function timeline(Shipment $shipment): array
    {
        $trackings = Tracking::query()
            ->with('branch:id,name')
            ->where('shipment_id', $shipment->id)
            ->get()
            ->map(fn (Tracking $tracking): array => [
                'status' => (string) $tracking->getRawOriginal('status'),
                'note' => $tracking->description,
                'location' => (string) ($tracking->branch?->name ?? ''),
                'happened_at' => $this->date($tracking->getRawOriginal('status_updated_at')),
            ]);

        $updates = ShipmentUpdate::query()
            ->where('shipment_id', $shipment->id)
            ->get()
            ->map(fn (ShipmentUpdate $update): array => [
                'status' => (string) $update->getRawOriginal('status'),
                'note' => $update->remarks,
                'location' => (string) ($update->location ?? ''),
                'happened_at' => $this->date($update->getRawOriginal('updated_at')),
            ]);

        return $trackings->concat($updates)
            ->sortByDesc(fn (array $row): int => $row['happened_at']?->getTimestamp() ?? 0)
            ->values()
            ->all();
    }

    /**
     * Whether the receiver has confirmed taking the shipment: the is_received flag, or — for
     * deliveries recorded before that flag was always set — a receiver signature on file.
     */
    public function isReceived(Shipment $shipment): bool
    {
        if ($shipment->is_received) {
            return true;
        }

        return ShipmentDelivery::query()
            ->where('shipment_id', $shipment->id)
            ->whereNotNull('receiver_signature')
            ->where('receiver_signature', '<>', '')
            ->exists();
    }

    /**
     * The items, grouped by receiver: item name, quantity, box number and a vehicle's
     * year/make/model, with receiver names cut to first name and last initial.
     *
     * @return list<array{receiver: string, items: list<array{name: string, quantity: int, box: string, vehicle: bool}>}>
     */
    public function items(Shipment $shipment): array
    {
        // shipment_items.shipment_id is back-filled after save, so older rows may only be
        // linked through their receiver — accept either.
        return ShipmentItem::query()
            ->with(['receiver:id,receiver_name', 'product:id,name'])
            ->where(fn ($query) => $query
                ->where('shipment_id', $shipment->id)
                ->orWhereHas('receiver', fn ($receiver) => $receiver->where('shipment_id', $shipment->id)))
            ->get()
            ->sortBy(fn (ShipmentItem $item): string => ($item->receiver?->receiver_name ?? '').'|'.$item->box_no)
            ->groupBy(fn (ShipmentItem $item): string => (string) $item->receiver_id)
            ->map(fn ($items): array => [
                'receiver' => self::maskName($items->first()->receiver?->receiver_name),
                'items' => $items->map(fn (ShipmentItem $item): array => [
                    'name' => $this->itemName($item),
                    'quantity' => (int) $item->quantity,
                    'box' => trim((string) $item->box_no),
                    'vehicle' => (bool) $item->is_vehicle,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * "Kwame Asante Mensah" -> "Kwame M.". Enough for a recipient to recognise their own
     * name, not enough to identify a stranger's.
     */
    public static function maskName(?string $name): string
    {
        $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);

        if (! $parts) {
            return 'Receiver';
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        return $parts[0].' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.';
    }

    /**
     * Presentation for a shipments.status value: badge label, the stage it lights up
     * (0 = booked, not yet picked up; null = cancelled, outside the tracker), badge class
     * and icon. A delivered shipment with confirmed receipt shows as Received.
     *
     * @return array{label: string, step: ?int, class: string, icon: string}
     */
    public static function statusMeta(string $status, bool $received = false): array
    {
        $status = strtolower(trim($status));

        if ($status === 'delivered' && $received) {
            $status = 'received';
        }

        return match ($status) {
            'pending' => ['label' => 'Booked — Awaiting Pickup', 'step' => 0, 'class' => 'warning', 'icon' => 'fa-clipboard-list'],
            'pickup' => ['label' => 'Picked Up', 'step' => 1, 'class' => 'warning', 'icon' => 'fa-box'],
            'warehouse' => ['label' => 'At Our Warehouse', 'step' => 2, 'class' => 'warning', 'icon' => 'fa-warehouse'],
            'loaded' => ['label' => 'Loaded', 'step' => 3, 'class' => 'warning', 'icon' => 'fa-boxes-stacked'],
            'shipped' => ['label' => 'Shipped', 'step' => 4, 'class' => 'info', 'icon' => 'fa-ship'],
            'in_transit', 'in transit' => ['label' => 'In Transit', 'step' => 5, 'class' => 'info', 'icon' => 'fa-earth-africa'],
            'port', 'cleared' => ['label' => 'At Harbour / Port GH', 'step' => 6, 'class' => 'info', 'icon' => 'fa-anchor'],
            'sorting' => ['label' => 'Sorting', 'step' => 7, 'class' => 'info', 'icon' => 'fa-layer-group'],
            'delivered' => ['label' => 'Delivered', 'step' => 8, 'class' => 'success', 'icon' => 'fa-truck-fast'],
            'received' => ['label' => 'Received', 'step' => 9, 'class' => 'success', 'icon' => 'fa-circle-check'],
            'cancelled' => ['label' => 'Cancelled', 'step' => null, 'class' => 'danger', 'icon' => 'fa-circle-xmark'],
            default => ['label' => ucfirst($status), 'step' => 0, 'class' => 'secondary', 'icon' => 'fa-box'],
        };
    }

    private function itemName(ShipmentItem $item): string
    {
        $name = trim((string) $item->product?->name);

        if ($item->is_vehicle) {
            $vehicle = trim(implode(' ', array_filter([$item->vehicle_year, $item->vehicle_make, $item->vehicle_model])));
            $name = $vehicle !== '' ? $vehicle : ($name !== '' ? $name : 'Vehicle');
        }

        return $name !== '' ? $name : 'Item';
    }

    private function date(mixed $value): ?CarbonInterface
    {
        if (blank($value) || str_starts_with((string) $value, '0000')) {
            return null;
        }

        return Carbon::parse($value);
    }
}

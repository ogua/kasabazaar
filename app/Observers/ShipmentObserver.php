<?php

namespace App\Observers;

use App\Models\Shipment;
use App\Models\ShipmentUpdate;
use App\Models\User;
use App\Notifications\ShipmentStatusUpdated;
use App\Services\PushNotificationService;
use App\Services\ShipmentNotifier;

class ShipmentObserver
{
    private static array $statusLabels = [
        'pending' => 'Shipment Received',
        'pickup' => 'Out for Pickup',
        'shipped' => 'In Transit',
        'cleared' => 'Customs Cleared',
        'delivered' => 'Delivered',
        'cancelled' => 'Shipment Cancelled',
    ];

    public function created(Shipment $shipment): void
    {
        if ($shipment->suppressLifecycleAlerts) {
            return;
        }

        ShipmentNotifier::sent($shipment, 'created');
    }

    public function updating(Shipment $shipment): void
    {
        if ($shipment->isDirty('msc_tracking_number') && filled($shipment->msc_tracking_number)) {
            $shipment->msc_tracking_updated_at = now();
        }

        if ($shipment->isDirty('status') && self::statusValue($shipment) === 'delivered' && blank($shipment->delivered_at)) {
            $shipment->delivered_at = now();
        }
    }

    public function updated(Shipment $shipment): void
    {
        $alertsEnabled = ! $shipment->suppressLifecycleAlerts;

        if ($alertsEnabled && $shipment->wasChanged('msc_tracking_number') && filled($shipment->msc_tracking_number)) {
            ShipmentNotifier::sent($shipment, 'msc_updated');
        }

        if ($shipment->wasChanged('is_received') && $shipment->is_received) {
            ShipmentUpdate::create([
                'shipment_id' => $shipment->id,
                'location' => self::place($shipment, true),
                'status' => 'received',
                'remarks' => 'Receiver confirmed receipt',
            ]);
        }

        if (! $shipment->wasChanged('status')) {
            return;
        }

        $this->recordStatusHistory($shipment);

        $status = $shipment->status instanceof \BackedEnum
            ? $shipment->status->value
            : $shipment->status;

        $label = self::$statusLabels[$status] ?? 'Shipment Updated';
        $ref = $shipment->shipping_reference ?? 'your shipment';

        // Email + SMS to the sender (and receivers on dispatch/delivery)
        if ($alertsEnabled) {
            ShipmentNotifier::sent($shipment, 'status:'.$status);
        }

        // Notify the client (customer user linked to this shipment's client)
        $clientUserId = User::where('client_id', $shipment->client_id)
            ->whereNotNull('client_id')
            ->value('id');

        if ($clientUserId) {
            $clientUser = User::find($clientUserId);

            if ($clientUser) {
                // Database notification (in-app notification centre)
                $clientUser->notify(new ShipmentStatusUpdated($shipment));

                // Push notification
                app(PushNotificationService::class)->sendToUser(
                    $clientUserId,
                    $label,
                    "Update for {$ref}: {$label}.",
                    ['shipment_id' => $shipment->id, 'status' => $status, 'type' => 'shipment_status']
                );
            }
        }
    }

    /**
     * Write a status-history row for this status change, unless the caller already
     * wrote one (the "Update Status" action, which collects its own location/notes).
     * This is what the public tracking page's Status History reads, so every path that
     * changes a status — admin edit, Record Delivery, mobile API, trips, bulk container
     * jobs — must leave a row here.
     */
    private function recordStatusHistory(Shipment $shipment): void
    {
        if ($shipment->statusHistoryRecorded) {
            return;
        }

        $status = self::statusValue($shipment);
        $note = $shipment->statusHistoryNote ?? [];

        // Where the goods are at each stage: still at origin until they've been
        // dispatched, at the destination from customs clearance onwards.
        $atDestination = in_array($status, ['cleared', 'delivered'], true);

        ShipmentUpdate::create([
            'shipment_id' => $shipment->id,
            'location' => filled($note['location'] ?? null) ? $note['location'] : self::place($shipment, $atDestination),
            'status' => $status,
            'remarks' => filled($note['remarks'] ?? null) ? $note['remarks'] : (self::$statusLabels[$status] ?? 'Status updated'),
        ]);
    }

    /**
     * origin_branch_id / destination_branch_id hold a place name picked in the
     * "Shipping Route" form (Michigan, Illinois, ... Ghana), not a branch id —
     * although older rows may hold a real branch id, so resolve that first.
     */
    private static function place(Shipment $shipment, bool $destination): string
    {
        $branch = $destination ? $shipment->destinationBranch : $shipment->originBranch;
        if ($branch) {
            return $branch->name;
        }

        $value = (string) ($destination ? $shipment->destination_branch_id : $shipment->origin_branch_id);

        return \Illuminate\Support\Str::isUuid($value) ? '' : $value;
    }

    private static function statusValue(Shipment $shipment): string
    {
        return $shipment->status instanceof \BackedEnum
            ? $shipment->status->value
            : (string) $shipment->status;
    }
}

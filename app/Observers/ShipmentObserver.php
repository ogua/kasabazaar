<?php

namespace App\Observers;

use App\Models\Shipment;
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
    }

    public function updated(Shipment $shipment): void
    {
        $alertsEnabled = ! $shipment->suppressLifecycleAlerts;

        if ($alertsEnabled && $shipment->wasChanged('msc_tracking_number') && filled($shipment->msc_tracking_number)) {
            ShipmentNotifier::sent($shipment, 'msc_updated');
        }

        if (! $shipment->wasChanged('status')) {
            return;
        }

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
}

<?php

namespace App\Notifications;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ShipmentStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Shipment $shipment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'       => 'Shipment Update',
            'body'        => "Your shipment {$this->shipment->shipping_reference} is now " . ucfirst($this->shipment->status->value ?? $this->shipment->status),
            'type'        => 'shipment_status_updated',
            'shipment_id' => $this->shipment->id,
            'reference'   => $this->shipment->shipping_reference,
            'status'      => $this->shipment->status instanceof \BackedEnum
                ? $this->shipment->status->value
                : $this->shipment->status,
        ];
    }

    public function toFcm(): array
    {
        return [
            'title' => 'Shipment Update',
            'body'  => "Your shipment {$this->shipment->shipping_reference} is now " . ucfirst($this->shipment->status->value ?? $this->shipment->status),
            'data'  => [
                'type'        => 'shipment_status_updated',
                'shipment_id' => $this->shipment->id,
            ],
        ];
    }
}

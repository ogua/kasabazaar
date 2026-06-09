<?php

namespace App\Notifications;

use App\Models\PickupSchedule;
use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PickupConverted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PickupSchedule $pickup,
        public readonly Shipment $shipment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'       => 'Shipment Created',
            'body'        => "Your pickup has been processed. Shipment {$this->shipment->shipping_reference} is now pending.",
            'type'        => 'pickup_converted',
            'pickup_id'   => $this->pickup->id,
            'shipment_id' => $this->shipment->id,
            'reference'   => $this->shipment->shipping_reference,
        ];
    }

    public function toFcm(): array
    {
        return [
            'title' => 'Shipment Created',
            'body'  => "Shipment {$this->shipment->shipping_reference} has been created from your pickup.",
            'data'  => [
                'type'        => 'pickup_converted',
                'shipment_id' => $this->shipment->id,
            ],
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Shipment;
use App\Models\ShipmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ShipmentRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ShipmentRequest $shipmentRequest,
        public readonly Shipment $shipment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'       => 'Shipment Request Approved',
            'body'        => "Your shipment request has been approved. Shipment {$this->shipment->shipping_reference} is now being processed.",
            'type'        => 'shipment_request_approved',
            'request_id'  => $this->shipmentRequest->id,
            'shipment_id' => $this->shipment->id,
            'reference'   => $this->shipment->shipping_reference,
        ];
    }
}

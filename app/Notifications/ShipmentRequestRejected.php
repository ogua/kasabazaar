<?php

namespace App\Notifications;

use App\Models\ShipmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ShipmentRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ShipmentRequest $shipmentRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Shipment Request Not Approved',
            'body' => 'We could not approve your shipment request: '.$this->shipmentRequest->rejection_reason,
            'type' => 'shipment_request_rejected',
            'request_id' => $this->shipmentRequest->id,
            'reason' => $this->shipmentRequest->rejection_reason,
        ];
    }
}

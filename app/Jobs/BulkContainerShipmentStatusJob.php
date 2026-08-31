<?php

namespace App\Jobs;

use App\Enums\ShippingStatus;
use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkContainerShipmentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $containerNumber,
        public readonly string $newStatus,
        public readonly ?string $note,
        public readonly bool $notifyClients = true,
    ) {}

    public function handle(): void
    {
        $shipments = Shipment::query()
            ->where('container_number', '=', $this->containerNumber)
            ->get();

        $statusEnum = ShippingStatus::from($this->newStatus);

        foreach ($shipments as $shipment) {
            // The status change fires ShipmentObserver, which sends the client
            // (and, on dispatch/delivery, receiver) email + SMS lifecycle alert.
            $shipment->suppressLifecycleAlerts = ! $this->notifyClients;
            $shipment->status = $statusEnum;
            $shipment->save();
        }
    }
}

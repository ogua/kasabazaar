<?php

namespace App\Jobs;

use App\Enums\ShippingStatus;
use App\Models\Shipment;
use App\Service\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

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
            ->with('client')
            ->where('container_number', '=', $this->containerNumber)
            ->get();

        $containerRef = 'CON'.$this->containerNumber;
        $statusEnum = ShippingStatus::from($this->newStatus);
        $updatedAt = now()->format('j M Y, g:i A');

        foreach ($shipments as $shipment) {
            $shipment->status = $statusEnum;
            $shipment->save();

            $client = $shipment->client;
            if (! $client) {
                continue;
            }

            if (! $this->notifyClients) {
                continue;
            }

            $receiverName = $shipment->receivers()->value('receiver_name');

            if ($client->email) {
                try {
                    Mail::send('emails.shipment_status_updated', [
                        'clientName' => $client->name,
                        'shipmentRef' => $shipment->shipping_reference,
                        'containerRef' => $containerRef,
                        'status' => $this->newStatus,
                        'note' => $this->note,
                        'updatedAt' => $updatedAt,
                        'receiverName' => $receiverName,
                    ], fn ($msg) => $msg
                        ->to($client->email)
                        ->subject("Shipment Update — {$shipment->shipping_reference}")
                    );
                } catch (\Throwable $e) {
                    logger()->error("Shipment status email failed for {$shipment->shipping_reference}: ".$e->getMessage());
                }
            }

            if ($client->phone) {
                $sms = "Hi {$client->name}, your shipment {$shipment->shipping_reference} ({$containerRef}) is now "
                    .ucfirst($this->newStatus).'.';

                if ($this->note) {
                    $sms .= ' Note: '.$this->note;
                }

                $sms .= ' — Rose Door to Door';

                NotificationService::sendSmsToSender($client->phone, $sms);
            }
        }
    }
}

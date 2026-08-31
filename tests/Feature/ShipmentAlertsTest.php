<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Notifications\ShipmentAlert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShipmentAlertsTest extends TestCase
{
    use DatabaseTransactions;

    private function makeShipment(array $overrides = []): Shipment
    {
        $client = Client::create([
            'branch_id' => (string) Str::uuid(),
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'phone' => '+233201234567',
        ]);

        return Shipment::create(array_merge([
            'client_id' => $client->id,
            'branch_id' => $client->branch_id,
            'origin_branch_id' => 'US-WAREHOUSE',
            'destination_branch_id' => 'ACCRA',
            'status' => 'pending',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'total' => 500,
        ], $overrides));
    }

    private function addReceivers(Shipment $shipment, int $count = 2): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Receiver::create([
                'shipment_id' => $shipment->id,
                'receiver_name' => "Kofi {$i}",
                'receiver_phone' => "+23324000000{$i}",
                'receiver_email' => "kofi{$i}@example.com",
            ]);
        }
    }

    public function test_creating_a_shipment_alerts_the_sender_only(): void
    {
        Notification::fake();

        $shipment = $this->makeShipment();
        $this->addReceivers($shipment);

        Notification::assertSentOnDemand(
            ShipmentAlert::class,
            fn (ShipmentAlert $n) => $n->event === 'created'
        );
        Notification::assertSentOnDemandTimes(ShipmentAlert::class, 1);
    }

    public function test_non_dispatch_status_change_alerts_the_sender_only(): void
    {
        $shipment = $this->makeShipment();
        $this->addReceivers($shipment);

        Notification::fake();
        $shipment->update(['status' => 'pickup']);

        Notification::assertSentOnDemand(
            ShipmentAlert::class,
            fn (ShipmentAlert $n) => $n->event === 'status:pickup'
        );
        Notification::assertSentOnDemandTimes(ShipmentAlert::class, 1);
    }

    public function test_dispatch_status_change_also_alerts_receivers(): void
    {
        $shipment = $this->makeShipment();
        $this->addReceivers($shipment, 2);

        Notification::fake();
        $shipment->update(['status' => 'shipped']);

        Notification::assertSentOnDemand(
            ShipmentAlert::class,
            fn (ShipmentAlert $n) => $n->event === 'status:shipped'
        );
        // sender + 2 receivers
        Notification::assertSentOnDemandTimes(ShipmentAlert::class, 3);
    }

    public function test_delivered_status_change_alerts_sender_and_receivers(): void
    {
        $shipment = $this->makeShipment();
        $this->addReceivers($shipment, 1);

        Notification::fake();
        $shipment->update(['status' => 'delivered']);

        Notification::assertSentOnDemandTimes(ShipmentAlert::class, 2);
    }

    public function test_setting_an_msc_tracking_number_alerts_and_stamps_the_time(): void
    {
        $shipment = $this->makeShipment();

        Notification::fake();
        $shipment->update(['msc_tracking_number' => 'MEDUUL1234567']);

        Notification::assertSentOnDemand(
            ShipmentAlert::class,
            fn (ShipmentAlert $n) => $n->event === 'msc_updated'
        );
        $this->assertNotNull($shipment->fresh()->msc_tracking_updated_at);
    }

    public function test_recording_a_payment_alerts_the_sender(): void
    {
        $shipment = $this->makeShipment();

        Notification::fake();
        Payment::create([
            'shipment_id' => $shipment->id,
            'branch_id' => $shipment->branch_id,
            'payment_type' => 'credit',
            'currency' => 'USD',
            'amount_usd' => 100,
            'exchange_rate' => 12,
            'paying_method' => 'paystack',
            'paid_on' => now(),
        ]);

        Notification::assertSentOnDemand(
            ShipmentAlert::class,
            fn (ShipmentAlert $n) => $n->event === 'payment_received'
        );
    }
}

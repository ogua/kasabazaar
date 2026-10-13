<?php

namespace App\Services;

use App\Exceptions\PickupLinkUnavailableException;
use App\Models\Client;
use App\Models\PickupSchedule;
use App\Models\ShipmentRequest;
use App\Models\ShipmentRequestLink;
use App\Notifications\PickupRequestSubmitted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * The one place a client's shipment (pickup) request enters the system — from the
 * customer app or from a public pickup-request link. Records the request, books a
 * pickup schedule and alerts the admin.
 */
class ShipmentRequestSubmissionService
{
    /**
     * @param  array{receivers: array<int, array<string, mixed>>, pickup_location: string, preferred_pickup_at: string, notes?: ?string}  $data
     */
    public function submit(Client $client, array $data, ?ShipmentRequestLink $link = null): ShipmentRequest
    {
        $shipmentRequest = DB::transaction(function () use ($client, $data, $link) {
            if ($link) {
                $link = ShipmentRequestLink::query()->lockForUpdate()->findOrFail($link->id);

                if (! $link->isUsable()) {
                    throw new PickupLinkUnavailableException;
                }

                $link->increment('submissions_count', 1, ['last_submitted_at' => now()]);
            }

            return ShipmentRequest::create([
                'client_id' => $client->id,
                'status' => 'submitted',
                'source' => $link ? 'public_link' : 'app',
                'shipment_request_link_id' => $link?->id,
                'receivers' => $data['receivers'],
                'pickup_location' => $data['pickup_location'],
                'preferred_pickup_at' => $data['preferred_pickup_at'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $this->schedulePickup($client, $data);
        $this->notifyAdmin($shipmentRequest, $client);

        return $shipmentRequest;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function schedulePickup(Client $client, array $data): void
    {
        try {
            PickupSchedule::create([
                'branch_id' => $client->branch_id,
                'client_id' => $client->id,
                'scheduled_at' => $data['preferred_pickup_at'],
                'pickup_location' => $data['pickup_location'],
                'contact_phone' => $client->phone,
                'notes' => $data['notes'] ?? null,
                'status' => 'scheduled',
                'items_description' => collect($data['receivers'])
                    ->flatMap(fn ($r) => $r['items'] ?? [])
                    ->pluck('description')
                    ->implode(', '),
            ]);
        } catch (\Throwable) {
        }
    }

    /**
     * Alert staff over email, SMS and (when its template is mapped) WhatsApp.
     */
    private function notifyAdmin(ShipmentRequest $shipmentRequest, Client $client): void
    {
        $shipmentRequest->setRelation('client', $client);
        $notification = new PickupRequestSubmitted($shipmentRequest);

        try {
            $emails = AdminAlertRecipients::emails();

            if ($emails !== []) {
                Notification::route('mail', $emails)->notify($notification);
            }

            foreach (AdminAlertRecipients::phones() as $phone) {
                $route = Notification::route('sms', $phone);

                if (PickupRequestSubmitted::sendsWhatsappTo($phone)) {
                    $route->route('whatsapp', $phone);
                }

                $route->notify(new PickupRequestSubmitted($shipmentRequest));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

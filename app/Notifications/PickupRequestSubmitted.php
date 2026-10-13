<?php

namespace App\Notifications;

use App\Filament\Resources\ShipmentRequestResource;
use App\Models\ShipmentRequest;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsappChannel;
use App\Notifications\Messages\WhatsappTemplateMessage;
use App\Services\Whatsapp\WhatsappSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells staff a client has asked for a pickup, so someone attends to it. Sent to
 * the on-demand admin routes from AdminAlertRecipients: email, SMS, and WhatsApp
 * when the "pickup_request" template is mapped. Staff get the SMS as well as the
 * WhatsApp message, so a failed WhatsApp send does not trigger a second SMS.
 */
class PickupRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $whatsappFallsBackToSms = false;

    public function __construct(public ShipmentRequest $shipmentRequest)
    {
        $this->afterCommit = true;
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->routeNotificationFor('mail')) {
            $channels[] = 'mail';
        }

        if ($notifiable->routeNotificationFor('sms')) {
            $channels[] = SmsChannel::class;
        }

        if ($notifiable->routeNotificationFor('whatsapp')) {
            $channels[] = WhatsappChannel::class;
        }

        return $channels;
    }

    /**
     * Whether a phone number should also get the WhatsApp alert.
     */
    public static function sendsWhatsappTo(string $phone): bool
    {
        return WhatsappSettings::shouldSend($phone, true, WhatsappSettings::SENDER_LOGISTICS, 'pickup_request');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->shipmentRequest->loadMissing('client');

        return (new MailMessage)
            ->subject('New Pickup Request – '.($request->client?->name ?? 'Client'))
            ->view('emails.admin_shipment_request', [
                'request' => $request,
                'client' => $request->client,
                'reviewUrl' => $this->reviewUrl(),
            ]);
    }

    public function toSms(object $notifiable): string
    {
        $request = $this->shipmentRequest->loadMissing('client');
        $client = $request->client;

        return 'New pickup request from '.($client?->name ?? 'a client')
            .($client?->phone ? " ({$client->phone})" : '')
            .". Pickup: {$request->pickup_location}"
            .' on '.$request->preferred_pickup_at?->format('j M, g:i A')
            .'. Review: '.$this->reviewUrl();
    }

    public function toWhatsapp(object $notifiable): WhatsappTemplateMessage
    {
        $request = $this->shipmentRequest->loadMissing('client');

        return new WhatsappTemplateMessage(
            sender: WhatsappSettings::SENDER_LOGISTICS,
            event: 'pickup_request',
            params: [
                $request->client?->name ?? 'Client',
                $request->client?->phone ?? '—',
                $request->pickup_location,
                $request->preferred_pickup_at?->format('j M Y, g:i A') ?? '—',
            ],
            reference: 'pickup-request:'.$request->id,
        );
    }

    /**
     * The request's page in the admin panel, under the client's branch.
     */
    public function reviewUrl(): string
    {
        $branchId = $this->shipmentRequest->client?->branch_id;

        try {
            return ShipmentRequestResource::getUrl('view', ['record' => $this->shipmentRequest, 'tenant' => $branchId], panel: 'admin');
        } catch (\Throwable) {
            return url('/admin');
        }
    }
}

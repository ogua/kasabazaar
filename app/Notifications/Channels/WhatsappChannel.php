<?php

namespace App\Notifications\Channels;

use App\Models\WhatsappMessage;
use App\Notifications\Messages\WhatsappTemplateMessage;
use App\Services\Whatsapp\OguaWhatsappClient;
use App\Services\Whatsapp\PhoneNumber;
use App\Services\Whatsapp\WhatsappGatewayException;
use App\Services\Whatsapp\WhatsappSettings;
use Illuminate\Notifications\Notification;

/**
 * Sends a notification as an approved WhatsApp template through the Ogua
 * gateway. A notification only reaches this channel when its recipient is
 * eligible (see WhatsappSettings::shouldSend()); if WhatsApp then fails — now,
 * or later via the gateway's failed callback — the notification's SMS text is
 * sent instead, so the recipient still hears about it.
 */
class WhatsappChannel
{
    public function __construct(private OguaWhatsappClient $client) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsapp')) {
            return;
        }

        $order = $notification->order ?? null;
        $phone = $notifiable->routeNotificationFor('whatsapp', $notification)
            ?? $order?->deliveryDetail?->phone
            ?? $notifiable->phone
            ?? null;

        /** @var WhatsappTemplateMessage|null $message */
        $message = $notification->toWhatsapp($notifiable);

        if (! $phone || ! $message) {
            return;
        }

        $template = WhatsappSettings::templateFor($message->sender, $message->event);
        $e164 = PhoneNumber::toE164($phone);

        $record = WhatsappMessage::create([
            'notification_type' => $notification::class,
            'event' => $message->event,
            'sender' => $message->sender,
            'phone' => $e164 ?? $phone,
            'template' => $template['template'] ?? '',
            'reference' => $message->reference,
            'status' => WhatsappMessage::STATUS_PENDING,
            'fallback_sms_body' => method_exists($notification, 'toSms') ? $notification->toSms($notifiable) : null,
            'fallback_phone' => $phone,
        ]);

        if (! $template || ! $e164) {
            $record->markFailed('NOT_ELIGIBLE', 'No template mapped for this event, or the number is not a valid international number.');
            $record->sendFallbackSms();

            return;
        }

        try {
            $result = $this->client->send(
                sender: $message->sender,
                to: $e164,
                template: $template['template'],
                language: $template['language'],
                bodyParams: $message->bodyParams(),
                buttonUrlSuffix: $message->buttonUrlSuffix,
                reference: $message->reference,
                idempotencyKey: $notification->id.':'.$e164,
            );

            $record->update([
                'gateway_message_id' => $result['id'] ?? null,
                'status' => $result['status'] ?? WhatsappMessage::STATUS_QUEUED,
            ]);
        } catch (WhatsappGatewayException $e) {
            logger()->warning('WhatsApp send failed, falling back to SMS: '.$e->getMessage());

            $record->markFailed((string) ($e->status ?? 'GATEWAY_ERROR'), $e->getMessage());
            $record->sendFallbackSms();
        }
    }
}

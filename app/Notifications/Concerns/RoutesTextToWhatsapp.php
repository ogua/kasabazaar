<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsappChannel;
use App\Notifications\Messages\WhatsappTemplateMessage;
use App\Services\Whatsapp\WhatsappSettings;

/**
 * For notifications sent to a user (marketplace customers and vendors): the
 * text channel is WhatsApp when that user opted in and the event has a
 * template, otherwise SMS as before. Mail and database channels are unaffected.
 *
 * @property-read \App\Models\EcommerceOrder $order
 */
trait RoutesTextToWhatsapp
{
    abstract public function toWhatsapp(object $notifiable): ?WhatsappTemplateMessage;

    /**
     * @return class-string
     */
    protected function textChannel(object $notifiable): string
    {
        $message = $this->toWhatsapp($notifiable);
        $hasConsent = method_exists($notifiable, 'hasWhatsappConsent') && $notifiable->hasWhatsappConsent();
        $phone = $this->order?->deliveryDetail?->phone ?? $notifiable->phone ?? null;

        return $message && WhatsappSettings::shouldSend($phone, $hasConsent, $message->sender, $message->event)
            ? WhatsappChannel::class
            : SmsChannel::class;
    }

    /**
     * The parameters every customer-facing order event shares: name, order number, status.
     */
    protected function orderStatusMessage(object $notifiable, string $status): WhatsappTemplateMessage
    {
        return new WhatsappTemplateMessage(
            sender: WhatsappSettings::SENDER_MARKETPLACE,
            event: 'order_status',
            params: [$notifiable->name ?? 'Customer', $this->order->order_number, $status],
            buttonUrlSuffix: $this->order->order_number,
            reference: $this->order->order_number,
        );
    }
}

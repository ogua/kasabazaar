<?php

namespace App\Notifications\Ecommerce;

use App\Models\EcommerceOrder;
use App\Notifications\Concerns\RoutesTextToWhatsapp;
use App\Notifications\Messages\WhatsappTemplateMessage;
use App\Services\Whatsapp\WhatsappSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EcommerceOrderPaymentConfirmed extends Notification implements ShouldQueue
{
    use Queueable, RoutesTextToWhatsapp;

    public function __construct(public readonly EcommerceOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', $this->textChannel($notifiable)];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment Confirmed — {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("We've received payment for order {$this->order->order_number}. It will move to processing shortly.")
            ->action('Track Your Order', $this->trackingUrl());
    }

    public function toSms(object $notifiable): string
    {
        return "Payment confirmed for order {$this->order->order_number}. Track: {$this->trackingUrl()}";
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Payment Confirmed',
            'body' => "Payment for order {$this->order->order_number} has been confirmed.",
            'type' => 'ecommerce_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'payment_gateway' => $this->order->payment_gateway,
        ];
    }

    private function trackingUrl(): string
    {
        return rtrim(config('app.frontend_url'), '/')."/track-order?order_number={$this->order->order_number}";
    }

    public function toWhatsapp(object $notifiable): ?WhatsappTemplateMessage
    {
        return new WhatsappTemplateMessage(
            sender: WhatsappSettings::SENDER_MARKETPLACE,
            event: 'order_payment',
            params: [$notifiable->name ?? 'Customer', $this->order->order_number, 'GHS '.number_format((float) $this->order->total_ghs, 2)],
            buttonUrlSuffix: $this->order->order_number,
            reference: $this->order->order_number,
        );
    }
}

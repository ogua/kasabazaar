<?php

namespace App\Notifications\Ecommerce;

use App\Models\EcommerceOrder;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EcommerceOrderDispatched extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly EcommerceOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', SmsChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tracking = $this->order->shipment?->tracking_number;

        $mail = (new MailMessage)
            ->subject("Order Dispatched — {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Order {$this->order->order_number} is on its way!");

        if ($tracking) {
            $mail->line("Tracking number: {$tracking}");
        }

        return $mail->action('Track Your Order', $this->trackingUrl());
    }

    public function toSms(object $notifiable): string
    {
        $tracking = $this->order->shipment?->tracking_number;

        return "Order {$this->order->order_number} is on its way!".($tracking ? " Tracking: {$tracking}." : '')." Track: {$this->trackingUrl()}";
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Order Dispatched',
            'body' => "Order {$this->order->order_number} is on its way! Tracking: {$this->order->shipment?->tracking_number}",
            'type' => 'ecommerce_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'tracking_number' => $this->order->shipment?->tracking_number,
        ];
    }

    private function trackingUrl(): string
    {
        return rtrim(config('app.frontend_url'), '/')."/track-order?order_number={$this->order->order_number}";
    }
}

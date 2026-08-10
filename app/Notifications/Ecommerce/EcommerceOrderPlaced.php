<?php

namespace App\Notifications\Ecommerce;

use App\Models\EcommerceOrder;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EcommerceOrderPlaced extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly EcommerceOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', SmsChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order Placed — {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Thanks for your order! Order {$this->order->order_number} has been placed and is awaiting review.")
            ->line("Total: GHS {$this->order->total_ghs}")
            ->action('Track Your Order', $this->trackingUrl())
            ->line('We will let you know as soon as your order is approved.');
    }

    public function toSms(object $notifiable): string
    {
        return "Your order {$this->order->order_number} has been placed. Total GHS {$this->order->total_ghs}. Track: {$this->trackingUrl()}";
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Order Placed',
            'body' => "Your order {$this->order->order_number} has been placed and is awaiting staff review.",
            'type' => 'ecommerce_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_ghs' => $this->order->total_ghs,
        ];
    }

    private function trackingUrl(): string
    {
        return rtrim(config('app.frontend_url'), '/')."/track-order?order_number={$this->order->order_number}";
    }
}

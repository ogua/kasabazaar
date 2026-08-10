<?php

namespace App\Notifications\Ecommerce;

use App\Models\EcommerceOrder;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EcommerceOrderApproved extends Notification implements ShouldQueue
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
            ->subject("Order Approved — {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Order {$this->order->order_number} has been approved. Please complete payment of GHS {$this->order->total_ghs} to proceed.")
            ->action('Track Your Order', $this->trackingUrl());
    }

    public function toSms(object $notifiable): string
    {
        return "Order {$this->order->order_number} approved. Please pay GHS {$this->order->total_ghs}. Track: {$this->trackingUrl()}";
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Order Approved — Payment Required',
            'body' => "Order {$this->order->order_number} has been approved. Please complete payment of GHS {$this->order->total_ghs}.",
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

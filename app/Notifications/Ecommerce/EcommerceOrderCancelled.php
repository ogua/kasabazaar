<?php

namespace App\Notifications\Ecommerce;

use App\Models\EcommerceOrder;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EcommerceOrderCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly EcommerceOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', SmsChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Order Cancelled — {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Order {$this->order->order_number} has been cancelled.");

        if ($this->order->cancelled_reason) {
            $mail->line("Reason: {$this->order->cancelled_reason}");
        }

        return $mail->action('View Order', $this->trackingUrl());
    }

    public function toSms(object $notifiable): string
    {
        return "Order {$this->order->order_number} has been cancelled.";
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Order Cancelled',
            'body' => "Order {$this->order->order_number} has been cancelled.".($this->order->cancelled_reason ? " Reason: {$this->order->cancelled_reason}" : ''),
            'type' => 'ecommerce_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }

    private function trackingUrl(): string
    {
        return rtrim(config('app.frontend_url'), '/')."/track-order?order_number={$this->order->order_number}";
    }
}

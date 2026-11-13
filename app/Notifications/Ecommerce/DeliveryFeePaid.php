<?php

namespace App\Notifications\Ecommerce;

use App\Models\EcommerceOrder;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The customer paid the delivery cost: the delivery team will now contact them to plan delivery.
 */
class DeliveryFeePaid extends Notification implements ShouldQueue
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
            ->subject("Delivery cost received — {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line('Thank you. We have received your delivery payment of GHS '.number_format((float) $this->order->delivery_fee_ghs, 2)." for order {$this->order->order_number}.")
            ->line('Our delivery team will contact you shortly on '.($this->order->deliveryDetail?->phone ?? 'your phone number').' to agree how and when your delivery will be made.');
    }

    public function toSms(object $notifiable): string
    {
        return "Order {$this->order->order_number}: delivery payment received. Our delivery team will call you soon to plan your delivery.";
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Delivery payment received',
            'body' => "Our delivery team will contact you to plan delivery of order {$this->order->order_number}.",
            'type' => 'ecommerce_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}

<?php

namespace App\Notifications\Ecommerce;

use App\Models\EcommerceOrder;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The delivery team agreed a delivery time with the customer.
 */
class DeliveryPlanned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly EcommerceOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', SmsChannel::class];
    }

    private function when(): string
    {
        return $this->order->delivery_scheduled_at?->format('D j M Y, g:i a') ?? 'the agreed time';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Delivery planned — {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your delivery for order {$this->order->order_number} is planned for {$this->when()}.");

        if ($this->order->delivery_plan_notes) {
            $message->line("Delivery details: {$this->order->delivery_plan_notes}");
        }

        return $message->line('If anything changes, please let our delivery team know.');
    }

    public function toSms(object $notifiable): string
    {
        return "Order {$this->order->order_number}: delivery planned for {$this->when()}. Our delivery team will be in touch if anything changes.";
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Delivery planned',
            'body' => "Delivery of order {$this->order->order_number} is planned for {$this->when()}.",
            'type' => 'ecommerce_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}

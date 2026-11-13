<?php

namespace App\Notifications\Ecommerce;

use App\Models\EcommerceOrder;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The shipping team has entered the delivery cost for an order: the customer, who
 * bears it, is asked to pay it. Mail, SMS and in-app (WhatsApp needs an approved template).
 */
class DeliveryFeeQuoted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly EcommerceOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', SmsChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Delivery cost for your order {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Our shipping team has reviewed your items for order {$this->order->order_number}.")
            ->line('Delivery cost: GHS '.number_format((float) $this->order->delivery_fee_ghs, 2).'. This is paid by you, separately from your items.');

        if ($this->order->delivery_fee_notes) {
            $message->line("Note from the shipping team: {$this->order->delivery_fee_notes}");
        }

        return $message
            ->action('Pay delivery cost', $this->orderUrl())
            ->line('Once it is paid, our delivery team will contact you to plan the delivery.');
    }

    public function toSms(object $notifiable): string
    {
        return "Order {$this->order->order_number}: delivery cost is GHS ".number_format((float) $this->order->delivery_fee_ghs, 2).". Please pay it to arrange delivery: {$this->orderUrl()}";
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Delivery cost ready',
            'body' => "Delivery for order {$this->order->order_number} costs GHS ".number_format((float) $this->order->delivery_fee_ghs, 2).'. Pay it to arrange delivery.',
            'type' => 'ecommerce_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'delivery_fee_ghs' => $this->order->delivery_fee_ghs,
        ];
    }

    private function orderUrl(): string
    {
        $callback = parse_url((string) config('ecommerce.checkout_callback_urls.web'));
        $base = isset($callback['host']) ? ($callback['scheme'] ?? 'https').'://'.$callback['host'] : config('app.frontend_url');

        return rtrim($base, '/').'/account/orders/'.$this->order->id;
    }
}

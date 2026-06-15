<?php

namespace App\Notifications\Ecommerce;

use App\Models\EcommerceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EcommerceOrderPaymentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly EcommerceOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
}

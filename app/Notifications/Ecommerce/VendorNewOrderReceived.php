<?php

namespace App\Notifications\Ecommerce;

use App\Models\EcommerceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VendorNewOrderReceived extends Notification implements ShouldQueue
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
            'title' => 'New Order Received',
            'body' => "You have a new order {$this->order->order_number} awaiting payment.",
            'type' => 'vendor_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_ghs' => $this->order->total_ghs,
        ];
    }
}

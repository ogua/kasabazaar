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

class VendorNewOrderReceived extends Notification implements ShouldQueue
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
            ->subject("New Order Received — {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("You have a new order {$this->order->order_number} awaiting the customer's payment.")
            ->line("Total: GHS {$this->order->total_ghs}")
            ->line('Manage it from your vendor dashboard.');
    }

    public function toSms(object $notifiable): string
    {
        return "New order {$this->order->order_number} received, GHS {$this->order->total_ghs}. Awaiting customer payment.";
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

    public function toWhatsapp(object $notifiable): ?WhatsappTemplateMessage
    {
        return new WhatsappTemplateMessage(
            sender: WhatsappSettings::SENDER_MARKETPLACE,
            event: 'vendor_new_order',
            params: [$notifiable->name ?? 'Vendor', $this->order->order_number, 'GHS '.number_format((float) $this->order->total_ghs, 2)],
            buttonUrlSuffix: $this->order->order_number,
            reference: $this->order->order_number,
        );
    }
}

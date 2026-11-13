<?php

namespace App\Notifications\Ecommerce;

use App\Models\EcommerceOrder;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A customer-facing update on where an order (or a return) is in our warehouse flow.
 * Mail, SMS and in-app; WhatsApp would need an approved template per message.
 */
class OrderUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly EcommerceOrder $order,
        public readonly string $title,
        public readonly string $body,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', SmsChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->title} — {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line($this->body)
            ->action('View your order', $this->orderUrl());
    }

    public function toSms(object $notifiable): string
    {
        return "Order {$this->order->order_number}: {$this->body}";
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'type' => 'ecommerce_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }

    private function orderUrl(): string
    {
        $callback = parse_url((string) config('ecommerce.checkout_callback_urls.web'));
        $base = isset($callback['host']) ? ($callback['scheme'] ?? 'https').'://'.$callback['host'] : config('app.frontend_url');

        return rtrim($base, '/').'/account/orders/'.$this->order->id;
    }
}

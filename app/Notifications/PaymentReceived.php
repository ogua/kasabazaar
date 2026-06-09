<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'      => 'Payment Received',
            'body'       => "Your payment of USD {$this->payment->amount_usd} has been recorded.",
            'type'       => 'payment_received',
            'payment_id' => $this->payment->id,
            'amount_usd' => $this->payment->amount_usd,
            'amount_ghs' => $this->payment->amount_ghs,
        ];
    }

    public function toFcm(): array
    {
        return [
            'title' => 'Payment Received',
            'body'  => "Your payment of USD {$this->payment->amount_usd} has been recorded.",
            'data'  => [
                'type'       => 'payment_received',
                'payment_id' => $this->payment->id,
            ],
        ];
    }
}

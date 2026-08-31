<?php

namespace App\Notifications;

use App\Models\Shipment;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification for every point in a shipment's life where the client (and,
 * on dispatch/delivery, the receivers) should hear from us: creation, each
 * status change, an MSC tracking number becoming available, and every payment.
 *
 * It is delivered over email + SMS to on-demand routes built by
 * {@see \App\Services\ShipmentNotifier}. In-app / push notifications stay with
 * the dedicated notifications fired from the observers.
 */
class ShipmentAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public Shipment $shipment,
        public string $event,
        public array $context = [],
    ) {
        $this->afterCommit = true;
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->routeNotificationFor('mail')) {
            $channels[] = 'mail';
        }

        if ($notifiable->routeNotificationFor('sms')) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $shipment = $this->shipment;
        $ref = $shipment->shipping_reference ?? $shipment->tracking_number ?? 'your shipment';

        $mail = (new MailMessage)
            ->subject($this->headline().' — '.$ref)
            ->greeting('Hi '.($this->context['recipient_name'] ?? 'there').',')
            ->line($this->sentence());

        if ($shipment->tracking_number) {
            $mail->line('Tracking number: **'.$shipment->tracking_number.'**');
        }

        if ($shipment->msc_tracking_number) {
            $mail->line('MSC tracking number: **'.$shipment->msc_tracking_number.'**')
                ->line('[Track your container live on MSC]('.$shipment->mscTrackingUrl().')');
        }

        $balance = (float) $shipment->outstanding_balance;

        $mail->line('Total: USD '.number_format((float) $shipment->total, 2)
            .'  |  Paid: USD '.number_format((float) $shipment->amount_paid, 2)
            .'  |  Balance: USD '.number_format($balance, 2));

        if ($this->portalUrl()) {
            $mail->action($balance > 0 ? 'View shipment & pay balance' : 'View shipment activity', $this->portalUrl());
        }

        return $mail->line('Thank you for shipping with RDD Shipping.');
    }

    public function toSms(object $notifiable): string
    {
        $shipment = $this->shipment;
        $ref = $shipment->shipping_reference ?? $shipment->tracking_number ?? 'shipment';
        $balance = (float) $shipment->outstanding_balance;

        $parts = ["RDD Shipping: {$ref} — {$this->sentence()}"];

        if ($this->event === 'msc_updated' && $shipment->msc_tracking_number) {
            $parts[] = "MSC no: {$shipment->msc_tracking_number}. Live: ".$shipment->mscTrackingUrl();
        }

        if ($balance > 0) {
            $parts[] = 'Balance USD '.number_format($balance, 2).'.';
        }

        if ($this->portalUrl()) {
            $parts[] = 'Details & pay: '.$this->portalUrl();
        }

        return implode(' ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'shipment_id' => $this->shipment->id,
            'reference' => $this->shipment->shipping_reference,
        ];
    }

    private function portalUrl(): ?string
    {
        return $this->shipment->public_view_token
            ? route('public-shipment-view', $this->shipment->public_view_token)
            : null;
    }

    private function headline(): string
    {
        return match ($this->event) {
            'created' => 'Shipment created',
            'status:pending' => 'Shipment received',
            'status:pickup' => 'Out for pickup',
            'status:shipped' => 'Shipment in transit',
            'status:cleared' => 'Customs cleared',
            'status:delivered' => 'Shipment delivered',
            'status:cancelled' => 'Shipment cancelled',
            'msc_updated' => 'Container tracking available',
            'container_cleared' => 'Container cleared',
            'container_update' => 'Container update',
            'payment_received' => 'Payment received',
            default => 'Shipment update',
        };
    }

    private function sentence(): string
    {
        return match ($this->event) {
            'created' => 'Your shipment has been created. You can track it and make payments any time from the link below.',
            'status:pending' => 'Your shipment has been received and logged.',
            'status:pickup' => 'Your shipment is out for pickup.',
            'status:shipped' => 'Your shipment is now in transit.',
            'status:cleared' => 'Your shipment has cleared customs.',
            'status:delivered' => 'Your shipment has been delivered.',
            'status:cancelled' => 'Your shipment has been cancelled. Please contact us if this is unexpected.',
            'msc_updated' => 'An MSC tracking number has been added to your shipment so you can follow it in real time.',
            'container_cleared' => 'Your container has cleared customs.'.$this->noteSuffix(),
            'container_update' => 'Your container clearance status has been updated.'.$this->noteSuffix(),
            'payment_received' => $this->paymentSentence(),
            default => 'There is an update on your shipment.',
        };
    }

    private function noteSuffix(): string
    {
        $note = trim((string) ($this->context['note'] ?? ''));

        return $note === '' ? '' : ' Note: '.$note;
    }

    private function paymentSentence(): string
    {
        $payment = $this->context['payment'] ?? null;
        $amount = $payment ? number_format((float) ($payment->amount_usd ?? $payment->amount), 2) : null;

        return $amount
            ? "We have received your payment of USD {$amount}. Thank you."
            : 'We have received a payment on your shipment. Thank you.';
    }
}

<?php

namespace App\Notifications\Channels;

use App\Service\NotificationService;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $order = $notification->order ?? null;
        $phone = $order?->deliveryDetail?->phone ?? $notifiable->phone ?? null;

        if (! $phone) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (! $message) {
            return;
        }

        NotificationService::sendSmsToSender($phone, $message);
    }
}

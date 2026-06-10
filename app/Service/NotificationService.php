<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;

class NotificationService
{
    public static function sendMailToSender($email, $message)
    {
        logger($email . '' . $message);
    }

    public static function sendSmsToSender(string $phone, string $message): void
    {
        try {
            Http::post('https://apps.mnotify.net/smsapi', [
                'key'       => config('services.mnotify.key'),
                'to'        => $phone,
                'msg'       => $message,
                'sender_id' => config('services.mnotify.sender', 'RDDSHIP'),
            ]);
        } catch (\Throwable $e) {
            logger()->error('MNotify SMS failed: ' . $e->getMessage());
        }
    }
}

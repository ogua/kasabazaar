<?php

namespace App\Service;

use App\Service\SystemSetting;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    public static function sendMailToSender($email, $message)
    {
        logger($email . '' . $message);
    }

    public static function sendSmsToSender(string $phone, string $message): void
    {
        $driver = SystemSetting::get('sms_driver', config('services.sms.default', 'twilio'));

        match ($driver) {
            'mnotify' => self::sendViaMNotify($phone, $message),
            default   => self::sendViaTwilio($phone, $message),
        };
    }

    private static function sendViaTwilio(string $phone, string $message): void
    {
        $sid   = SystemSetting::get('twilio_sid',   config('services.twilio.sid'));
        $token = SystemSetting::get('twilio_token', config('services.twilio.token'));
        $from  = SystemSetting::get('twilio_from',  config('services.twilio.from'));

        if (!$sid || !$token || !$from) {
            logger()->warning('Twilio SMS skipped — credentials not configured.');
            return;
        }

        try {
            Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To'   => $phone,
                    'Body' => $message,
                ]);
        } catch (\Throwable $e) {
            logger()->error('Twilio SMS failed: ' . $e->getMessage());
        }
    }

    private static function sendViaMNotify(string $phone, string $message): void
    {
        $key    = SystemSetting::get('mnotify_key',    config('services.mnotify.key'));
        $sender = SystemSetting::get('mnotify_sender', config('services.mnotify.sender', 'RDDSHIP'));

        if (!$key) {
            logger()->warning('MNotify SMS skipped — API key not configured.');
            return;
        }

        try {
            Http::post('https://apps.mnotify.net/smsapi', [
                'key'       => $key,
                'to'        => $phone,
                'msg'       => $message,
                'sender_id' => $sender,
            ]);
        } catch (\Throwable $e) {
            logger()->error('MNotify SMS failed: ' . $e->getMessage());
        }
    }
}

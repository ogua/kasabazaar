<?php

namespace App\Service;

use App\Models\Payment;
use App\Services\Whatsapp\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public static function sendPaymentConfirmation(Payment $payment): void
    {
        $shipment = $payment->shipment;
        if (! $shipment) {
            return;
        }

        $client = $shipment->client;
        if (! $client) {
            return;
        }

        $invoiceUrl = url("/shipping-invoice-download/{$shipment->id}");
        $receiptUrl = url("/payment-receipt/{$payment->id}");

        if ($client->email) {
            try {
                Mail::send('emails.payment_received', [
                    'clientName' => $client->name,
                    'amountUsd' => number_format($payment->amount_usd, 2),
                    'amountGhs' => $payment->amount_ghs ? number_format($payment->amount_ghs, 2) : null,
                    'method' => $payment->paying_method,
                    'reference' => $payment->payment_reference ?? $payment->payment_ref,
                    'paidOn' => $payment->paid_on
                                       ? \Carbon\Carbon::parse($payment->paid_on)->format('j M Y, g:i A')
                                       : now()->format('j M Y'),
                    'shipmentRef' => $shipment->shipping_reference,
                    'invoiceUrl' => $invoiceUrl,
                    'receiptUrl' => $receiptUrl,
                ], fn ($msg) => $msg
                    ->to($client->email)
                    ->subject('Payment Confirmed – '.$shipment->shipping_reference)
                );
            } catch (\Throwable $e) {
                logger()->error('Payment email failed: '.$e->getMessage());
            }
        }

        if ($client->phone) {
            $msg = "Payment confirmed! USD {$payment->amount_usd} for shipment "
                 ."{$shipment->shipping_reference} via {$payment->paying_method}. "
                 ."Download invoice: {$invoiceUrl}";
            self::sendSmsToSender($client->phone, $msg);
        }
    }

    public static function sendMailToSender($email, $message)
    {
        logger($email.''.$message);
    }

    public static function sendSmsToSender(string $phone, string $message): void
    {
        $driver = self::resolveSmsDriverForPhone($phone);

        match ($driver) {
            'arkesel' => self::sendViaArkesel($phone, $message),
            'mnotify' => self::sendViaMNotify($phone, $message),
            'infobip' => self::sendViaInfobip($phone, $message),
            default => self::sendViaTwilio($phone, $message),
        };
    }

    /**
     * Route by the destination number: Ghana numbers (+233… or a local
     * 0XXXXXXXXX) go through Arkesel, everything else through the international
     * provider (Infobip by default). A number we cannot classify falls back to
     * the configured default driver.
     */
    public static function resolveSmsDriverForPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '233') && strlen($digits) >= 11 && strlen($digits) <= 12) {
            return 'arkesel';
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return 'arkesel';
        }

        if (strlen($digits) === 9 && ! str_starts_with($digits, '0')) {
            // bare Ghana subscriber number, e.g. 24XXXXXXX
            return 'arkesel';
        }

        if ($digits !== '') {
            return SystemSetting::get('sms_international_driver', config('services.sms.international', 'infobip'));
        }

        return SystemSetting::get('sms_driver', config('services.sms.default', 'infobip'));
    }

    /**
     * Normalise a Ghana number to the 233XXXXXXXXX form Arkesel expects.
     */
    private static function normalizeGhana(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '233')) {
            return $digits;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '233'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '233'.$digits;
        }

        return $digits;
    }

    private static function sendViaArkesel(string $phone, string $message): void
    {
        $key = SystemSetting::get('arkesel_key', config('services.arkesel.key'));
        $sender = SystemSetting::get('arkesel_sender', config('services.arkesel.sender', 'RDDSHIP'));

        if (! $key) {
            logger()->warning('Arkesel SMS skipped — API key not configured.');

            return;
        }

        try {
            $response = Http::withHeaders(['api-key' => $key])
                ->asForm()
                ->post('https://sms.oguaschoolz.com/api/v2/sms/send', [
                    'sender' => $sender,
                    'message' => $message,
                    'recipients' => [self::normalizeGhana($phone)],
                ]);

            if ($response->failed()) {
                logger()->error('Arkesel SMS failed: HTTP '.$response->status().' '.$response->body());
            }
        } catch (\Throwable $e) {
            logger()->error('Arkesel SMS failed: '.$e->getMessage());
        }
    }

    /**
     * Infobip SMS. Without an API key the message still goes out through Twilio,
     * so switching the default provider never silences international alerts.
     */
    private static function sendViaInfobip(string $phone, string $message): void
    {
        $key = SystemSetting::get('infobip_sms_key', config('services.infobip.sms_key'));
        $sender = SystemSetting::get('infobip_sms_sender', config('services.infobip.sms_sender'));
        $baseUrl = rtrim((string) SystemSetting::get('infobip_base_url', config('services.infobip.base_url')), '/');
        $to = PhoneNumber::toE164($phone);

        if (! $key) {
            logger()->warning('Infobip SMS not configured — sending through Twilio instead.');
            self::sendViaTwilio($phone, $message);

            return;
        }

        if ($to === null) {
            logger()->warning("Infobip SMS skipped — \"{$phone}\" is not a valid international number.");

            return;
        }

        try {
            $response = Http::withHeaders(['Authorization' => 'App '.$key])
                ->acceptJson()
                ->timeout(15)
                ->post("{$baseUrl}/sms/2/text/advanced", [
                    'messages' => [array_filter([
                        'from' => $sender ?: null,
                        'destinations' => [['to' => ltrim($to, '+')]],
                        'text' => $message,
                    ])],
                ]);

            if ($response->failed()) {
                logger()->error('Infobip SMS failed: HTTP '.$response->status().' '.$response->body());
            }
        } catch (\Throwable $e) {
            logger()->error('Infobip SMS failed: '.$e->getMessage());
        }
    }

    private static function sendViaTwilio(string $phone, string $message): void
    {
        $sid = SystemSetting::get('twilio_sid', config('services.twilio.sid'));
        $token = SystemSetting::get('twilio_token', config('services.twilio.token'));
        $from = SystemSetting::get('twilio_from', config('services.twilio.from'));

        if (! $sid || ! $token || ! $from) {
            logger()->warning('Twilio SMS skipped — credentials not configured.');

            return;
        }

        try {
            Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $phone,
                    'Body' => $message,
                ]);
        } catch (\Throwable $e) {
            logger()->error('Twilio SMS failed: '.$e->getMessage());
        }
    }

    private static function sendViaMNotify(string $phone, string $message): void
    {
        $key = SystemSetting::get('mnotify_key', config('services.mnotify.key'));
        $sender = SystemSetting::get('mnotify_sender', config('services.mnotify.sender', 'RDDSHIP'));

        if (! $key) {
            logger()->warning('MNotify SMS skipped — API key not configured.');

            return;
        }

        try {
            Http::post('https://apps.mnotify.net/smsapi', [
                'key' => $key,
                'to' => $phone,
                'msg' => $message,
                'sender_id' => $sender,
            ]);
        } catch (\Throwable $e) {
            logger()->error('MNotify SMS failed: '.$e->getMessage());
        }
    }
}

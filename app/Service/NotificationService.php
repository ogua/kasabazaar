<?php

namespace App\Service;

use App\Models\Payment;
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
            default => self::sendViaTwilio($phone, $message),
        };
    }

    /**
     * Route by the destination number: Ghana numbers (+233… or a local
     * 0XXXXXXXXX) go through Arkesel, everything else through Twilio. A number
     * we cannot classify falls back to the configured default driver.
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
            return 'twilio';
        }

        return SystemSetting::get('sms_driver', config('services.sms.default', 'twilio'));
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
            Http::withHeaders(['api-key' => $key])
                ->post('https://sms.arkesel.com/api/v2/sms/send', [
                    'sender' => $sender,
                    'message' => $message,
                    'recipients' => [self::normalizeGhana($phone)],
                ]);
        } catch (\Throwable $e) {
            logger()->error('Arkesel SMS failed: '.$e->getMessage());
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

<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentPaymentAttempt;
use App\Notifications\ShipmentAlert;
use App\Services\Whatsapp\WhatsappSettings;
use Illuminate\Support\Facades\Notification;

/**
 * Single entry point for lifecycle alerts on a shipment. Every trigger
 * (creation, status change, MSC update, payment) calls
 * {@see ShipmentNotifier::sent()} — it decides who hears about it and fans the
 * {@see ShipmentAlert} out over email plus WhatsApp or SMS.
 *
 * Rule: the sender (the shipment's client) is alerted at every stage; the
 * receivers are only alerted on dispatch, arrival at the Ghana port and delivery.
 * A payment receipt also goes to whoever paid online, and always by SMS: it is a
 * receipt the payer may need to show, not a marketing message.
 */
class ShipmentNotifier
{
    private const RECEIVER_EVENTS = ['status:shipped', 'status:port', 'status:delivered'];

    /**
     * Events whose text message always goes by SMS, never WhatsApp.
     */
    private const SMS_ONLY_EVENTS = ['payment_received'];

    /**
     * @param  array<string, mixed>  $context
     */
    public static function sent(?Shipment $shipment, string $event, array $context = []): void
    {
        if (! $shipment) {
            return;
        }

        try {
            self::ensurePortalToken($shipment);

            $recipients = self::recipients($shipment, $event, $context);

            $whatsappEvent = ShipmentAlert::whatsappEventFor($event);

            foreach ($recipients as $recipient) {
                // Email always goes when there is an address; the phone gets WhatsApp
                // (opted in, template mapped) or SMS — never both.
                $textRoute = $recipient['phone'] && ! in_array($event, self::SMS_ONLY_EVENTS, true) && WhatsappSettings::shouldSend(
                    $recipient['phone'],
                    $recipient['whatsapp_consent'],
                    WhatsappSettings::SENDER_LOGISTICS,
                    $whatsappEvent
                ) ? 'whatsapp' : 'sms';

                $routes = array_filter([
                    'mail' => $recipient['email'],
                    $textRoute => $recipient['phone'],
                ]);

                if ($routes === []) {
                    continue;
                }

                Notification::routes($routes)->notify(
                    new ShipmentAlert($shipment, $event, $context + ['recipient_name' => $recipient['name']])
                );
            }
        } catch (\Throwable $e) {
            logger()->error('ShipmentNotifier failed for '.$event.' on '.$shipment->id.': '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array{name: string, email: ?string, phone: ?string, whatsapp_consent: bool}>
     */
    private static function recipients(Shipment $shipment, string $event, array $context = []): array
    {
        $recipients = [];

        $client = $shipment->client()->first();
        if ($client) {
            $recipients[] = [
                'name' => $client->name ?: 'there',
                'email' => self::deliverableEmail($client->email),
                'phone' => self::clean($client->phone),
                'whatsapp_consent' => $client->hasWhatsappConsent(),
            ];
        }

        if (in_array($event, self::RECEIVER_EVENTS, true)) {
            foreach ($shipment->receivers()->get() as $receiver) {
                $recipients[] = [
                    'name' => $receiver->receiver_name ?: 'there',
                    'email' => self::deliverableEmail($receiver->receiver_email),
                    'phone' => self::clean($receiver->receiver_phone),
                    'whatsapp_consent' => $receiver->hasWhatsappConsent(),
                ];
            }
        }

        $payment = $context['payment'] ?? null;
        if ($event === 'payment_received' && $payment instanceof Payment && filled($payment->payment_ref)) {
            $attempt = ShipmentPaymentAttempt::where('reference', $payment->payment_ref)->first();

            if ($attempt && ($attempt->payer_email || $attempt->payer_phone)) {
                $recipients[] = [
                    'name' => 'there',
                    'email' => self::deliverableEmail($attempt->payer_email),
                    'phone' => self::clean($attempt->payer_phone),
                    'whatsapp_consent' => false,
                ];
            }
        }

        return self::dedupe($recipients);
    }

    /**
     * Drop any email address or phone number already being messaged, so nobody gets
     * the same alert twice when two parties share an address or number.
     *
     * @param  array<int, array{name: string, email: ?string, phone: ?string, whatsapp_consent: bool}>  $recipients
     * @return array<int, array{name: string, email: ?string, phone: ?string, whatsapp_consent: bool}>
     */
    private static function dedupe(array $recipients): array
    {
        $seenEmails = [];
        $seenPhones = [];
        $out = [];

        foreach ($recipients as $recipient) {
            $email = strtolower((string) $recipient['email']);
            $phone = substr(preg_replace('/\D+/', '', (string) $recipient['phone']), -9);

            if ($email === '' || isset($seenEmails[$email])) {
                $recipient['email'] = null;
            }

            if ($phone === '' || isset($seenPhones[$phone])) {
                $recipient['phone'] = null;
            }

            if ($recipient['email'] === null && $recipient['phone'] === null) {
                continue;
            }

            $seenEmails[$email] = true;
            $seenPhones[$phone] = true;
            $out[] = $recipient;
        }

        return $out;
    }

    /**
     * The address, unless it can never receive mail: the RFC 2606 / 6761 reserved
     * test domains (example.com, *.test, ...) that placeholder and demo records use.
     * Queuing mail to them only produces failed jobs.
     */
    private static function deliverableEmail(?string $email): ?string
    {
        $email = self::clean($email);
        $domain = strtolower((string) substr(strrchr((string) $email, '@') ?: '', 1));

        if ($email === null || $domain === '') {
            return null;
        }

        return preg_match('/(^|\.)(example\.(com|net|org)|test|example|invalid|localhost)$/', $domain) ? null : $email;
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function ensurePortalToken(Shipment $shipment): void
    {
        if (blank($shipment->public_view_token)) {
            $shipment->forceFill(['public_view_token' => Shipment::generatePublicViewToken()])->saveQuietly();
        }
    }
}

<?php

namespace App\Services;

use App\Models\Shipment;
use App\Notifications\ShipmentAlert;
use Illuminate\Support\Facades\Notification;

/**
 * Single entry point for lifecycle alerts on a shipment. Every trigger
 * (creation, status change, MSC update, payment) calls
 * {@see ShipmentNotifier::sent()} — it decides who hears about it and fans the
 * {@see ShipmentAlert} out over email + SMS.
 *
 * Rule: the sender (the shipment's client) is alerted at every stage; the
 * receivers are only alerted on dispatch and delivery.
 */
class ShipmentNotifier
{
    private const RECEIVER_EVENTS = ['status:shipped', 'status:delivered'];

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

            $recipients = self::recipients($shipment, $event);

            foreach ($recipients as $recipient) {
                $routes = array_filter([
                    'mail' => $recipient['email'],
                    'sms' => $recipient['phone'],
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
     * @return array<int, array{name: string, email: ?string, phone: ?string}>
     */
    private static function recipients(Shipment $shipment, string $event): array
    {
        $recipients = [];

        $client = $shipment->client()->first();
        if ($client) {
            $recipients[] = [
                'name' => $client->name ?: 'there',
                'email' => self::clean($client->email),
                'phone' => self::clean($client->phone),
            ];
        }

        if (in_array($event, self::RECEIVER_EVENTS, true)) {
            foreach ($shipment->receivers()->get() as $receiver) {
                $recipients[] = [
                    'name' => $receiver->receiver_name ?: 'there',
                    'email' => self::clean($receiver->receiver_email),
                    'phone' => self::clean($receiver->receiver_phone),
                ];
            }
        }

        return self::dedupe($recipients);
    }

    /**
     * @param  array<int, array{name: string, email: ?string, phone: ?string}>  $recipients
     * @return array<int, array{name: string, email: ?string, phone: ?string}>
     */
    private static function dedupe(array $recipients): array
    {
        $seen = [];
        $out = [];

        foreach ($recipients as $recipient) {
            $key = strtolower((string) $recipient['email']).'|'.preg_replace('/\D+/', '', (string) $recipient['phone']);

            if ($key === '|' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[] = $recipient;
        }

        return $out;
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

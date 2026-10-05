<?php

namespace App\Services\Whatsapp;

use App\Service\SystemSetting;

/**
 * WhatsApp gateway settings (SystemSetting group "whatsapp", edited on the
 * WhatsApp Settings page) and the fixed list of events that can go out over
 * WhatsApp, with the body parameters each event supplies — in order — to
 * whichever approved template an admin maps to it.
 */
class WhatsappSettings
{
    public const SENDER_LOGISTICS = 'logistics';

    public const SENDER_MARKETPLACE = 'marketplace';

    /**
     * @var array<string, array<string, array{label: string, params: list<string>}>>
     */
    public const EVENTS = [
        self::SENDER_LOGISTICS => [
            'created' => ['label' => 'Shipment created', 'params' => ['Recipient name', 'Shipment ref']],
            'status' => ['label' => 'Shipment status changed', 'params' => ['Recipient name', 'Shipment ref', 'New status', 'Balance due']],
            'msc_updated' => ['label' => 'Container tracking available', 'params' => ['Recipient name', 'Shipment ref', 'MSC tracking no.']],
            'container_update' => ['label' => 'Container cleared / updated', 'params' => ['Recipient name', 'Shipment ref', 'Update']],
            'payment_received' => ['label' => 'Shipment payment received', 'params' => ['Recipient name', 'Amount paid', 'Shipment ref']],
        ],
        self::SENDER_MARKETPLACE => [
            'order_status' => ['label' => 'Order placed / approved / processing / packed / delivered / cancelled / refunded', 'params' => ['Customer name', 'Order no.', 'New status']],
            'order_dispatched' => ['label' => 'Order dispatched', 'params' => ['Customer name', 'Order no.', 'Tracking no.']],
            'order_payment' => ['label' => 'Order payment confirmed', 'params' => ['Customer name', 'Order no.', 'Amount']],
            'vendor_new_order' => ['label' => 'Vendor: new order received', 'params' => ['Vendor name', 'Order no.', 'Order total']],
        ],
    ];

    public static function enabled(): bool
    {
        return (bool) SystemSetting::get('whatsapp_enabled', false)
            && filled(self::gatewayUrl())
            && filled(self::clientKey())
            && filled(self::clientSecret());
    }

    public static function gatewayUrl(): ?string
    {
        $url = SystemSetting::get('whatsapp_gateway_url');

        return $url ? rtrim($url, '/') : null;
    }

    public static function clientKey(): ?string
    {
        return SystemSetting::get('whatsapp_client_key');
    }

    public static function clientSecret(): ?string
    {
        return SystemSetting::get('whatsapp_client_secret');
    }

    public static function callbackSecret(): ?string
    {
        return SystemSetting::get('whatsapp_callback_secret');
    }

    /**
     * During rollout, WhatsApp only goes to these numbers (E.164); everyone else keeps getting SMS.
     *
     * @return list<string>
     */
    public static function testNumbers(): array
    {
        $raw = (string) SystemSetting::get('whatsapp_test_numbers', '');

        return array_values(array_filter(array_map(
            fn (string $number): ?string => PhoneNumber::toE164($number),
            preg_split('/[\s,]+/', $raw) ?: []
        )));
    }

    public static function templateSettingKey(string $sender, string $event): string
    {
        return "whatsapp_template_{$sender}_{$event}";
    }

    /**
     * The approved template mapped to an event, stored as "template_name|language".
     *
     * @return array{template: string, language: string}|null
     */
    public static function templateFor(string $sender, string $event): ?array
    {
        $value = (string) SystemSetting::get(self::templateSettingKey($sender, $event), '');

        if ($value === '') {
            return null;
        }

        [$template, $language] = array_pad(explode('|', $value, 2), 2, null);

        return ['template' => $template, 'language' => $language ?: 'en'];
    }

    /**
     * Whether this recipient should get the event over WhatsApp instead of SMS.
     */
    public static function shouldSend(?string $phone, bool $hasConsent, string $sender, string $event): bool
    {
        if (! $hasConsent || ! self::enabled() || ! self::templateFor($sender, $event)) {
            return false;
        }

        $e164 = PhoneNumber::toE164($phone);

        if ($e164 === null) {
            return false;
        }

        $testNumbers = self::testNumbers();

        return $testNumbers === [] || in_array($e164, $testNumbers, true);
    }
}

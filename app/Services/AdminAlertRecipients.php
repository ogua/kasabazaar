<?php

namespace App\Services;

use App\Service\SystemSetting;

/**
 * Staff contacts alerted about new client activity (pickup / shipment requests).
 * Set on the SMS Settings page; ADMIN_EMAIL / ADMIN_PHONE are the fallback.
 */
class AdminAlertRecipients
{
    /**
     * @return list<string>
     */
    public static function emails(): array
    {
        return self::split(SystemSetting::get('admin_alert_emails', config('app.admin_email')));
    }

    /**
     * @return list<string>
     */
    public static function phones(): array
    {
        return self::split(SystemSetting::get('admin_alert_phones', config('app.admin_phone')));
    }

    /**
     * @return list<string>
     */
    public static function split(mixed $value): array
    {
        return collect(preg_split('/[,;\n]+/', (string) $value))
            ->map(fn (string $part): string => trim($part))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}

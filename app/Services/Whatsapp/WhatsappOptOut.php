<?php

namespace App\Services\Whatsapp;

use App\Models\Client;
use App\Models\Receiver;
use App\Models\User;

/**
 * Applies a WhatsApp STOP (or START) reply to every client, receiver and user
 * stored with that number, whichever provider reported it.
 */
class WhatsappOptOut
{
    /**
     * Inbound keywords, matched case-insensitively against the whole reply.
     */
    public const STOP_KEYWORDS = ['STOP', 'STOPALL', 'UNSUBSCRIBE', 'OPT OUT', 'OPTOUT'];

    public const START_KEYWORDS = ['START', 'SUBSCRIBE', 'OPT IN', 'OPTIN'];

    public static function apply(string $phone, bool $optedOut): void
    {
        $variants = PhoneNumber::storedVariants($phone);

        if ($variants === []) {
            return;
        }

        $changes = $optedOut
            ? ['whatsapp_opted_out_at' => now()]
            : ['whatsapp_opted_out_at' => null, 'whatsapp_opt_in_at' => now()];

        Client::whereIn('phone', $variants)->update($changes);
        Receiver::whereIn('receiver_phone', $variants)->update($changes);
        User::whereIn('phone', $variants)->update($changes);
    }

    /**
     * Opt the sender in or out when an inbound reply is a STOP / START keyword.
     */
    public static function applyKeyword(string $phone, string $text): void
    {
        $keyword = strtoupper(trim(preg_replace('/\s+/', ' ', $text)));

        if (in_array($keyword, self::STOP_KEYWORDS, true)) {
            self::apply($phone, true);
        } elseif (in_array($keyword, self::START_KEYWORDS, true)) {
            self::apply($phone, false);
        }
    }
}

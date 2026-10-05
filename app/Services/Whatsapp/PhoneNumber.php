<?php

namespace App\Services\Whatsapp;

/**
 * Phone numbers are stored in mixed shapes (local 024…, bare 24…, +233…, or
 * any international E.164 from the phone input). WhatsApp needs E.164.
 */
class PhoneNumber
{
    /**
     * Normalise to E.164 (+233241234567), treating local-looking numbers as
     * Ghanaian. Returns null when the number can't be made into one.
     */
    public static function toE164(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '233'.substr($digits, 1);
        } elseif (strlen($digits) === 9 && ! str_starts_with($digits, '0')) {
            $digits = '233'.$digits;
        }

        if (strlen($digits) < 11 || strlen($digits) > 15 || str_starts_with($digits, '0')) {
            return null;
        }

        return '+'.$digits;
    }

    /**
     * Every shape the same number may be stored in, for exact-match lookups:
     * +233241234567, 233241234567, 0241234567, 241234567.
     *
     * @return list<string>
     */
    public static function storedVariants(string $phone): array
    {
        $e164 = self::toE164($phone);

        if ($e164 === null) {
            return [];
        }

        $digits = substr($e164, 1);
        $variants = [$e164, $digits];

        if (str_starts_with($digits, '233')) {
            $local = substr($digits, 3);
            $variants[] = '0'.$local;
            $variants[] = $local;
            $variants[] = '+233 '.$local;
        }

        return $variants;
    }
}

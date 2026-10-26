<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

/**
 * Turns what staff or clients type (an international phone number, a country,
 * region or city name) into the ids the country/state/city selects store.
 */
class LocationLookup
{
    /**
     * The country an international number (+233…, +1…) is dialled in, or null for
     * a local number with no country code or one that cannot be parsed.
     */
    public static function countryIdForPhone(?string $phone): ?int
    {
        $phone = trim((string) $phone);

        if (! str_starts_with($phone, '+')) {
            return null;
        }

        try {
            $util = PhoneNumberUtil::getInstance();
            $region = $util->getRegionCodeForNumber($util->parse($phone));
        } catch (NumberParseException) {
            return null;
        }

        if (blank($region) || $region === 'ZZ') {
            return null;
        }

        $id = Country::where('iso2', $region)->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * A country by its name, ISO2 or ISO3 code, ignoring case ("Ghana", "gh", "GHA").
     */
    public static function countryIdForName(?string $country): ?int
    {
        $country = trim((string) $country);

        if ($country === '') {
            return null;
        }

        if (ctype_digit($country) && Country::whereKey((int) $country)->exists()) {
            return (int) $country;
        }

        $id = Country::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($country)])
            ->orWhereRaw('LOWER(iso2) = ?', [mb_strtolower($country)])
            ->orWhereRaw('LOWER(iso3) = ?', [mb_strtolower($country)])
            ->value('id');

        return $id ? (int) $id : null;
    }

    public static function stateIdForName(?int $countryId, ?string $state): ?int
    {
        $state = trim((string) $state);

        if (! $countryId || $state === '') {
            return null;
        }

        $id = State::where('country_id', $countryId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($state)])
            ->value('id');

        return $id ? (int) $id : null;
    }

    public static function cityIdForName(?int $stateId, ?string $city): ?int
    {
        $city = trim((string) $city);

        if (! $stateId || $city === '') {
            return null;
        }

        $id = City::where('state_id', $stateId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($city)])
            ->value('id');

        return $id ? (int) $id : null;
    }
}

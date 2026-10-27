<?php

namespace App\Service;

use App\Models\ExchangeRateLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    protected string $defaultFrom = 'USD';

    protected string $defaultTo = 'GHS';

    /**
     * Sources staff enter by hand. On the same date they win over the daily API rate,
     * and the API sync never overwrites them.
     */
    public const STAFF_SOURCES = ['manual', 'bank', 'official'];

    /**
     * The current rate: the newest one dated today or earlier, whatever its source.
     * A rate staff set for a date wins over the API's rate for that same date, but a
     * newer API rate replaces an older staff rate — an old manual rate must never
     * stand in for today's.
     */
    public function getCurrentRate(string $from = 'USD', string $to = 'GHS', bool $useApi = false): float
    {
        $cacheKey = self::cacheKey($from, $to);

        return Cache::remember($cacheKey, 3600, function () use ($from, $to, $useApi) {
            $latest = ExchangeRateLog::where('from_currency', $from)
                ->where('to_currency', $to)
                ->where('rate_date', '<=', today())
                ->orderBy('rate_date', 'desc')
                ->orderByRaw("CASE WHEN source IN ('".implode("','", self::STAFF_SOURCES)."') THEN 0 ELSE 1 END")
                ->first();

            if ($latest) {
                return (float) $latest->rate;
            }

            // Only fetch from API if explicitly enabled
            if ($useApi) {
                $rate = $this->fetchRateFromApi($from, $to);

                if ($rate) {
                    // Log the rate but mark it as API
                    $this->logRate($rate, $from, $to, 'api');
                    logger()->info("Using API exchange rate: {$rate}");

                    return $rate;
                }
            }

            // PRIORITY 4: Default fallback rate
            logger()->warning('No exchange rate found, using default fallback: 12.0');

            return 12.0; // Default fallback rate for USD to GHS
        });
    }

    /**
     * Fetch rate from external API
     */
    protected function fetchRateFromApi(string $from, string $to): ?float
    {
        try {
            // Using exchangerate-api.com free tier
            $response = Http::timeout(10)
                ->get("https://api.exchangerate-api.com/v4/latest/{$from}");

            if ($response->successful()) {
                $data = $response->json();

                return $data['rates'][$to] ?? null;
            }
        } catch (\Exception $e) {
            logger()->warning('Failed to fetch exchange rate: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Get rate for a specific date
     */
    public function getRateForDate(Carbon $date, string $from = 'USD', string $to = 'GHS'): float
    {
        $rate = ExchangeRateLog::getRateForDate($date->format('Y-m-d'), $from, $to);

        if ($rate) {
            return $rate;
        }

        // If no historical rate, use current rate
        return $this->getCurrentRate($from, $to);
    }

    /**
     * Log exchange rate to database
     */
    public function logRate(float $rate, string $from = 'USD', string $to = 'GHS', ?string $source = null, ?string $userId = null): ExchangeRateLog
    {
        // The API sync must not overwrite a rate staff set for today.
        if ($source === 'api') {
            $staffRate = ExchangeRateLog::where('from_currency', $from)
                ->where('to_currency', $to)
                ->whereDate('rate_date', today())
                ->whereIn('source', self::STAFF_SOURCES)
                ->first();

            if ($staffRate) {
                return $staffRate;
            }
        }

        return ExchangeRateLog::updateOrCreate(
            [
                'from_currency' => $from,
                'to_currency' => $to,
                'rate_date' => today(),
            ],
            [
                'rate' => $rate,
                'source' => $source ?? 'manual',
                'recorded_by' => $userId,
            ]
        );
    }

    /**
     * Manually set today's rate
     */
    public function setTodayRate(float $rate, string $from = 'USD', string $to = 'GHS', ?string $userId = null): ExchangeRateLog
    {
        return $this->logRate($rate, $from, $to, 'manual', $userId);
    }

    public static function cacheKey(string $from, string $to): string
    {
        return "exchange_rate_{$from}_{$to}";
    }

    /**
     * Convert amount using current rate
     */
    public function convert(float $amount, string $from = 'USD', string $to = 'GHS'): float
    {
        $rate = $this->getCurrentRate($from, $to);

        return round($amount * $rate, 2);
    }

    /**
     * Convert amount using a specific rate
     */
    public function convertWithRate(float $amount, float $rate): float
    {
        return round($amount * $rate, 2);
    }

    /**
     * Get rate history for a period
     */
    public function getRateHistory(string $from = 'USD', string $to = 'GHS', int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return ExchangeRateLog::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('rate_date', '>=', now()->subDays($days))
            ->orderBy('rate_date', 'desc')
            ->get();
    }
}

<?php

namespace App\Service;

use App\Models\ExchangeRateLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ExchangeRateService
{
    protected string $defaultFrom = 'USD';
    protected string $defaultTo = 'GHS';

    /**
     * Get current exchange rate
     * PRIORITIZES MANUAL RATES - API fetching is disabled by default
     */
    public function getCurrentRate(string $from = 'USD', string $to = 'GHS', bool $useApi = false): float
    {
        // Try to get from cache first (cache for 1 hour)
        $cacheKey = "exchange_rate_{$from}_{$to}";

        return Cache::remember($cacheKey, 3600, function () use ($from, $to, $useApi) {
            // PRIORITY 1: Check for manually entered rates for today or the most recent date
            $manualRate = ExchangeRateLog::where('from_currency', $from)
                ->where('to_currency', $to)
                ->where('rate_date', '<=', today())
                ->whereIn('source', ['manual', 'bank', 'official']) // Prioritize manual sources
                ->orderBy('rate_date', 'desc')
                ->first();

            if ($manualRate) {
                logger()->info("Using manual exchange rate: {$manualRate->rate} from {$manualRate->rate_date}");
                return (float) $manualRate->rate;
            }

            // PRIORITY 2: Check for any rate in the database (including API rates)
            $anyRate = ExchangeRateLog::where('from_currency', $from)
                ->where('to_currency', $to)
                ->where('rate_date', '<=', today())
                ->orderBy('rate_date', 'desc')
                ->first();

            if ($anyRate) {
                logger()->info("Using database exchange rate: {$anyRate->rate} from {$anyRate->rate_date}");
                return (float) $anyRate->rate;
            }

            // PRIORITY 3: Only fetch from API if explicitly enabled
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
            logger()->warning("No exchange rate found, using default fallback: 12.0");
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
            logger()->warning("Failed to fetch exchange rate: " . $e->getMessage());
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
        // Clear cache
        Cache::forget("exchange_rate_{$from}_{$to}");

        return $this->logRate($rate, $from, $to, 'manual', $userId);
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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Service\ExchangeRateService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ExchangeRate extends Command
{
    protected $signature = 'app:exchange-rate';

    protected $description = 'Fetch and update the USD → GHS exchange rate from open.er-api.com';

    public function handle(ExchangeRateService $service): int
    {
        $this->info('Fetching USD/GHS exchange rate from open.er-api.com...');

        try {
            $response = Http::timeout(15)->get('https://open.er-api.com/v6/latest/USD');

            if (! $response->successful()) {
                $this->error("API request failed with status: {$response->status()}");
                return self::FAILURE;
            }

            $data = $response->json();

            if (($data['result'] ?? '') !== 'success') {
                $this->error('API returned an unsuccessful result.');
                return self::FAILURE;
            }

            $ghsRate = $data['rates']['GHS'] ?? null;

            if (! $ghsRate) {
                $this->error('GHS rate not found in API response.');
                return self::FAILURE;
            }

            logger("Fetched exchange rate from API: 1 USD = {$ghsRate} GHS");

            // Persist the rate (updateOrCreate for today) and bust the 1-hour cache
            $service->logRate((float) $ghsRate, 'USD', 'GHS', 'api');
            Cache::forget('exchange_rate_USD_GHS');

            $this->info("Rate updated successfully: 1 USD = {$ghsRate} GHS");
            $this->line("API last updated: " . ($data['time_last_update_utc'] ?? 'N/A'));

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to fetch exchange rate: {$e->getMessage()}");
            logger()->error('app:exchange-rate failed — ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Worksome\Exchange\Facades\Exchange;

class ExchangeRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:exchange-rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $exchangeRates = Exchange::rates('USD', ['GBP', 'EUR', 'GHS']);

        $rates = $exchangeRates->getRates();

        logger()->info('Exchange Rates:', $rates);
    }
}

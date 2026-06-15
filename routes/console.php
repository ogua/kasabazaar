<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Fetch latest USD → GHS exchange rate from open.er-api.com daily.
// The free-tier API resets at ~00:02 UTC; run at 00:05 UTC to get fresh data.
Schedule::command('app:cleanup-expired-ecommerce-carts')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('app:exchange-rate')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        logger()->error('Scheduled app:exchange-rate command failed.');
    });

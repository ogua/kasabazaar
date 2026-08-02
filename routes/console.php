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

// Generate year-end interest drafts for the previous year every January 2nd.
// Drafts require accountant review at Investors > Pending Interest Postings.
Schedule::command('app:post-year-end-investment-interest')
    ->yearlyOn(1, 2, '02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        logger()->error('Scheduled app:post-year-end-investment-interest command failed.');
    });

// Generate due periodic cash interest payouts for loan-type investments daily —
// unlike annual compounding interest, loan payment frequency varies per loan
// (monthly/quarterly/etc.), so this can't run on a single fixed calendar date.
Schedule::command('app:generate-investment-interest-payout-drafts')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        logger()->error('Scheduled app:generate-investment-interest-payout-drafts command failed.');
    });

// Runs ~2 weeks after year-end interest drafts are generated above, giving staff
// time to review and post that year's interest so the statement's ledger reflects
// finalized figures. Creates draft records only — staff add business updates and
// send from Investors > Annual Statements.
Schedule::command('app:generate-annual-investment-statements')
    ->yearlyOn(1, 15, '02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        logger()->error('Scheduled app:generate-annual-investment-statements command failed.');
    });

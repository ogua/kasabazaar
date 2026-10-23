<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Settle auctions as soon as their clock runs out, so winners are notified
// and the payment window opens within a minute of closing.
Schedule::command('app:close-ended-auctions')
    ->everyMinute()
    ->withoutOverlapping()
    ->onFailure(function () {
        logger()->error('Scheduled app:close-ended-auctions command failed.');
    });

Schedule::command('app:cleanup-expired-ecommerce-carts')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();

// Fetch latest USD → GHS exchange rate from open.er-api.com daily. Cedi checkouts for
// shipment balances are converted at this rate. The free-tier API resets at ~00:02 UTC;
// run at 00:05 UTC to get fresh data.
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

// Stored Paystack webhook calls are kept for webhook-client.delete_after_days, then pruned.
Schedule::command('model:prune', ['--model' => [\Spatie\WebhookClient\Models\WebhookCall::class]])
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();

// Confirms shipment checkouts whose callback and webhook were both missed.
Schedule::command('app:reconcile-shipment-payments')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Housekeeping. Failed jobs are kept a month for debugging; finished batches two days;
// expired password-reset links are cleared so old tokens cannot be replayed.
Schedule::command('queue:prune-failed', ['--hours' => 720])
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('queue:prune-batches', ['--hours' => 48])
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('auth:clear-resets')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

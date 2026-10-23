<?php

namespace App\Console\Commands;

use App\Enums\ShipmentPaymentAttemptStatus;
use App\Models\ShipmentPaymentAttempt;
use App\Services\ShipmentPaymentService;
use Illuminate\Console\Command;

/**
 * Safety net for shipment checkouts: a payment is normally confirmed by the payer
 * returning from Paystack or by the webhook, but either can be missed (browser
 * closed, network drop, webhook not reaching the server). This re-checks every
 * checkout still pending after a few minutes, for up to two days, and records any
 * that Paystack says were paid.
 */
class ReconcileShipmentPayments extends Command
{
    protected $signature = 'app:reconcile-shipment-payments
        {--min-age=5 : Minutes a checkout must have been pending before it is re-checked}
        {--max-age=2880 : Minutes after which a pending checkout is no longer re-checked}';

    protected $description = 'Re-verify pending Paystack shipment checkouts and record any that were paid';

    public function handle(ShipmentPaymentService $payments): int
    {
        $attempts = ShipmentPaymentAttempt::query()
            ->where('status', ShipmentPaymentAttemptStatus::Pending->value)
            ->where('created_at', '<=', now()->subMinutes((int) $this->option('min-age')))
            ->where('created_at', '>=', now()->subMinutes((int) $this->option('max-age')))
            ->oldest()
            ->limit(200)
            ->get();

        $recorded = 0;

        foreach ($attempts as $attempt) {
            if ($payments->refresh($attempt)->isPaid()) {
                $recorded++;
                $this->line("Recorded {$attempt->reference}");
            }
        }

        $this->info("Checked {$attempts->count()} pending checkout(s); {$recorded} newly confirmed.");

        return self::SUCCESS;
    }
}

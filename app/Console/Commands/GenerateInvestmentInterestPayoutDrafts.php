<?php

namespace App\Console\Commands;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentStatus;
use App\Models\Investment;
use App\Service\InvestmentInterestPayoutService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateInvestmentInterestPayoutDrafts extends Command
{
    protected $signature = 'app:generate-investment-interest-payout-drafts';

    protected $description = 'Generate due interest_payout rows for active loan investments whose next payout date has arrived, and advance their payout cursor.';

    public function handle(InvestmentInterestPayoutService $service): int
    {
        $investments = Investment::query()
            ->where('status', InvestmentStatus::active->value)
            ->where('capital_type', InvestmentCapitalType::loan->value)
            ->whereNotNull('payout_frequency')
            ->whereNotNull('next_payout_due_date')
            ->where('next_payout_due_date', '<=', now()->toDateString())
            ->get();

        if ($investments->isEmpty()) {
            $this->info('No active loan investments have a payout due today.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($investments as $investment) {
            try {
                $months = $investment->payout_frequency->months();
                $maturityDate = Carbon::parse($investment->maturity_date);
                $cursor = Carbon::parse($investment->next_payout_due_date);

                // The cursor is always start_date + N months (seeded that way, and
                // advanced by exactly one frequency each run), so the period it closes
                // out started exactly one frequency before it — mirrors the anchoring in
                // InvestmentInterestPayoutService::projectSchedule() so due dates always
                // land on the same day-of-month as the start date.
                $periodStart = $cursor->copy()->subMonths($months);

                // Clamp to the true maturity date — the cursor advances in fixed
                // increments and can overshoot a maturity date that doesn't land
                // exactly on a period boundary (e.g. a manually overridden maturity
                // date matching an already-signed physical agreement). Mirrors the
                // clamping in InvestmentInterestPayoutService::projectSchedule() so
                // the agreement's shown schedule matches what actually generates.
                $isFinalPeriod = $cursor->gte($maturityDate);
                $periodEnd = $isFinalPeriod ? $maturityDate->copy() : $cursor->copy();

                $payout = $service->generateDue($investment, $periodStart, $periodEnd, $periodEnd);

                $investment->update([
                    // Once the loan has reached its final scheduled period, stop
                    // advancing the cursor — principal becomes due at maturity instead,
                    // and nothing further should be generated past that point.
                    'next_payout_due_date' => $isFinalPeriod ? null : $cursor->copy()->addMonths($months),
                ]);

                $rows[] = [$investment->reference, $payout->amount, 'due row created'];
            } catch (\Throwable $e) {
                $rows[] = [$investment->reference, '-', $e->getMessage()];
                logger()->error("app:generate-investment-interest-payout-drafts failed for {$investment->reference}: {$e->getMessage()}");
            }
        }

        $this->table(['Investment', 'Amount', 'Result'], $rows);

        return self::SUCCESS;
    }
}

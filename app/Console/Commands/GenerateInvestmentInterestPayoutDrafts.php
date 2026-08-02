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
                $periodEnd = Carbon::parse($investment->next_payout_due_date);
                $periodStart = $investment->interestPayouts()->doesntExist()
                    ? Carbon::parse($investment->start_date)
                    : $periodEnd->copy()->subMonths($months)->addDay();

                $payout = $service->generateDue($investment, $periodStart, $periodEnd, $periodEnd);

                $investment->update([
                    'next_payout_due_date' => $periodEnd->copy()->addMonths($months),
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

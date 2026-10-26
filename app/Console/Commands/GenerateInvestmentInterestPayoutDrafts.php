<?php

namespace App\Console\Commands;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentStatus;
use App\Models\Investment;
use App\Service\InvestmentInterestPayoutService;
use App\Services\InvestmentWithdrawalApprovalService;
use Illuminate\Console\Command;

class GenerateInvestmentInterestPayoutDrafts extends Command
{
    protected $signature = 'app:generate-investment-interest-payout-drafts';

    protected $description = 'Generate due interest_payout rows for active loans (catching up any missed periods), and open the principal repayment for loans that have matured.';

    public function handle(InvestmentInterestPayoutService $service, InvestmentWithdrawalApprovalService $withdrawals): int
    {
        $today = now()->startOfDay();

        $investments = Investment::query()
            ->where('status', InvestmentStatus::active->value)
            ->where('capital_type', InvestmentCapitalType::loan->value)
            ->where(fn ($query) => $query->whereDate('next_payout_due_date', '<=', $today)
                ->orWhereDate('maturity_date', '<=', $today))
            ->get();

        if ($investments->isEmpty()) {
            $this->info('No active loan investments have a payout or principal repayment due today.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($investments as $investment) {
            try {
                if ($investment->payout_frequency) {
                    foreach ($service->generateDuePayouts($investment, $today) as $payout) {
                        $rows[] = [$investment->reference, $payout->due_date->toDateString(), $payout->amount, 'interest payout due'];
                    }
                }

                // At maturity the principal is owed back to the lender in full.
                if ($request = $withdrawals->requestLoanPrincipalRepayment($investment->fresh())) {
                    $rows[] = [$investment->reference, $investment->maturity_date->toDateString(), $investment->current_balance, "principal repayment request opened ({$request->id})"];
                }
            } catch (\Throwable $e) {
                $rows[] = [$investment->reference, '-', '-', $e->getMessage()];
                logger()->error("app:generate-investment-interest-payout-drafts failed for {$investment->reference}: {$e->getMessage()}");
            }
        }

        if ($rows === []) {
            $this->info('Every due period and matured principal already has a record.');

            return self::SUCCESS;
        }

        $this->table(['Investment', 'Due', 'Amount', 'Result'], $rows);

        return self::SUCCESS;
    }
}

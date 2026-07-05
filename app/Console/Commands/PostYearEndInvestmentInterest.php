<?php

namespace App\Console\Commands;

use App\Enums\InvestmentStatus;
use App\Models\Investment;
use App\Service\InvestmentInterestService;
use Illuminate\Console\Command;

class PostYearEndInvestmentInterest extends Command
{
    protected $signature = 'app:post-year-end-investment-interest {year? : Defaults to the previous calendar year}';

    protected $description = 'Generate draft interest-credit postings for all active investments not yet posted for the given year. Drafts require staff review before crediting.';

    public function handle(InvestmentInterestService $service): int
    {
        $year = (int) ($this->argument('year') ?? now()->subYear()->year);

        $investments = Investment::query()
            ->where('status', InvestmentStatus::active->value)
            ->where(function ($query) use ($year) {
                $query->whereNull('last_interest_posted_year')
                    ->orWhere('last_interest_posted_year', '<', $year);
            })
            ->get();

        if ($investments->isEmpty()) {
            $this->info("No active investments require an interest draft for {$year}.");

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($investments as $investment) {
            try {
                $draft = $service->generateDraft($investment, $year);
                $rows[] = [$investment->reference, $draft->credit, 'draft created'];
            } catch (\Throwable $e) {
                $rows[] = [$investment->reference, '-', $e->getMessage()];
                logger()->error("app:post-year-end-investment-interest failed for {$investment->reference}: {$e->getMessage()}");
            }
        }

        $this->table(['Investment', 'Computed Interest', 'Result'], $rows);
        $this->info('Drafts are pending staff review at Investors > Pending Interest Postings before they are credited.');

        return self::SUCCESS;
    }
}

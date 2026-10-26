<?php

namespace App\Console\Commands;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentStatus;
use App\Models\Investment;
use App\Service\InvestmentInterestService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PostYearEndInvestmentInterest extends Command
{
    protected $signature = 'app:post-year-end-investment-interest {year? : Defaults to the previous calendar year}';

    protected $description = 'Generate draft interest-credit postings for all active investments not yet posted for the given year. Drafts require staff review before crediting.';

    public function handle(InvestmentInterestService $service): int
    {
        $year = (int) ($this->argument('year') ?? now()->subYear()->year);

        $yearEnd = Carbon::create($year, 12, 31)->toDateString();

        // Loans are excluded: their interest is paid in cash on the payout schedule.
        // Anything not yet posted through Dec 31 is included — a tranche opened late in
        // the year, a year part-posted through an earlier date, or a missed prior year.
        $investments = Investment::query()
            ->where('status', InvestmentStatus::active->value)
            ->where('capital_type', InvestmentCapitalType::investment->value)
            ->whereDate('start_date', '<=', $yearEnd)
            ->where(function ($query) use ($year, $yearEnd) {
                $query->whereDate('last_interest_posted_through', '<', $yearEnd)
                    ->orWhere(fn ($noDate) => $noDate->whereNull('last_interest_posted_through')
                        ->where(fn ($noYear) => $noYear->whereNull('last_interest_posted_year')
                            ->orWhere('last_interest_posted_year', '<', $year)));
            })
            ->get();

        if ($investments->isEmpty()) {
            $this->info("No active investments require an interest draft for {$year}.");

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($investments as $investment) {
            try {
                foreach ($service->generateYearEndDrafts($investment, $year) as $draft) {
                    $rows[] = [
                        $investment->reference,
                        $draft->period_start->toDateString().' – '.$draft->period_end->toDateString(),
                        $draft->credit,
                        $draft->year < $year ? 'catch-up draft created' : 'draft created',
                    ];
                }
            } catch (\Throwable $e) {
                $rows[] = [$investment->reference, '-', '-', $e->getMessage()];
                logger()->error("app:post-year-end-investment-interest failed for {$investment->reference}: {$e->getMessage()}");
            }
        }

        $this->table(['Investment', 'Period', 'Computed Interest', 'Result'], $rows);
        $this->info('Drafts are pending staff review at Investors > Pending Interest Postings before they are credited.');

        return self::SUCCESS;
    }
}

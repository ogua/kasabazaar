<?php

namespace App\Console\Commands;

use App\Enums\InvestmentStatus;
use App\Models\InvestmentAnnualStatement;
use App\Models\Investor;
use Illuminate\Console\Command;

class GenerateAnnualInvestmentStatements extends Command
{
    protected $signature = 'app:generate-annual-investment-statements {year? : Defaults to the previous calendar year}';

    protected $description = 'Create draft annual investment statements for every investor with at least one funded investment. Drafts require staff to add business updates and send them from Investors > Annual Statements.';

    public function handle(): int
    {
        $year = (int) ($this->argument('year') ?? now()->subYear()->year);

        $investors = Investor::whereHas('investments', function ($query) {
            $query->where('status', '!=', InvestmentStatus::pending_payment->value);
        })->get();

        if ($investors->isEmpty()) {
            $this->info("No investors with funded investments found for {$year}.");

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($investors as $investor) {
            try {
                $statement = InvestmentAnnualStatement::firstOrCreate([
                    'investor_id' => $investor->id,
                    'year' => $year,
                ]);

                $rows[] = [$investor->name, $statement->wasRecentlyCreated ? 'created' : 'skipped (already exists)'];
            } catch (\Throwable $e) {
                $rows[] = [$investor->name, "failed: {$e->getMessage()}"];
                logger()->error("app:generate-annual-investment-statements failed for investor {$investor->id}: {$e->getMessage()}");
            }
        }

        $this->table(['Investor', 'Result'], $rows);
        $this->info('Draft statements are pending staff review at Investors > Annual Statements before they are sent.');

        return self::SUCCESS;
    }
}

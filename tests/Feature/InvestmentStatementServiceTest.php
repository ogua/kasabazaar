<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\InvestmentRateOverride;
use App\Models\InvestmentRateSetting;
use App\Models\Investor;
use App\Service\InvestmentInterestPayoutService;
use App\Service\InvestmentStatementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestmentStatementServiceTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Regression: the account statement used to call the compounding-investment
     * valuation for every investment regardless of type, and only rendered the
     * ledger (ignoring interestPayouts entirely) — so a loan's accrued-but-unpaid
     * interest never appeared anywhere on the statement. Must generate without
     * error and reflect accrued interest for a loan-only investor.
     */
    public function test_statement_pdf_reflects_accrued_loan_interest(): void
    {
        $investor = Investor::create(['name' => 'Statement Loan Investor', 'status' => 'active']);

        $loan = Investment::create([
            'investor_id' => $investor->id,
            'capital_type' => 'loan',
            'principal_amount' => 40000,
            'current_balance' => 40000,
            'start_date' => '2026-02-15',
            'contract_term_months' => 12,
            'payout_frequency' => 'quarterly',
            'maturity_date' => '2027-02-14',
            'status' => 'active',
        ]);
        InvestmentRateOverride::create(['investment_id' => $loan->id, 'year' => 2026, 'annual_rate' => 9]);

        $payoutService = app(InvestmentInterestPayoutService::class);
        $schedule = $payoutService->projectSchedule($loan->fresh());
        $payoutService->generateDue($loan, $schedule[0]['period_start'], $schedule[0]['period_end'], $schedule[0]['due_date']);
        $payoutService->generateDue($loan, $schedule[1]['period_start'], $schedule[1]['period_end'], $schedule[1]['due_date']);

        $pdf = InvestmentStatementService::generatePdf($investor->fresh());

        $this->assertGreaterThan(1000, strlen($pdf->output()));
    }

    public function test_statement_pdf_generates_for_investment_only_investor(): void
    {
        $investor = Investor::create(['name' => 'Statement Investment Investor', 'status' => 'active']);
        InvestmentRateSetting::firstOrCreate(['year' => now()->year], ['annual_rate' => 10]);

        Investment::create([
            'investor_id' => $investor->id,
            'capital_type' => 'investment',
            'principal_amount' => 10000,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $pdf = InvestmentStatementService::generatePdf($investor->fresh());

        $this->assertGreaterThan(1000, strlen($pdf->output()));
    }
}

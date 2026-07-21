<?php

namespace Tests\Feature;

use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\User;
use App\Service\InvestmentInterestService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestmentTransactionRevisionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_revising_an_interest_transaction_cascades_recalculation_to_later_rows_and_investment_balance(): void
    {
        $investor = Investor::create(['name' => 'Revision Investor', 'status' => 'active']);
        $editor = User::first() ?? User::factory()->create();

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2024-01-01',
            'status' => 'active',
            'current_balance' => 13500,
        ]);

        $row1 = InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'date' => '2024-12-31',
            'type' => InvestmentTransactionType::interest_credit->value,
            'op_balance' => 10000,
            'credit' => 1000,
            'year' => 2024,
            'posted' => true,
        ]);

        $row2 = InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'date' => '2025-12-31',
            'type' => InvestmentTransactionType::interest_credit->value,
            'op_balance' => 11000,
            'credit' => 1200,
            'year' => 2025,
            'posted' => true,
        ]);

        $row3 = InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'date' => '2026-12-31',
            'type' => InvestmentTransactionType::interest_credit->value,
            'op_balance' => 12200,
            'credit' => 1300,
            'year' => 2026,
            'posted' => true,
        ]);

        app(InvestmentInterestService::class)->reviseInterestTransaction($row1, 800, $editor, 'Data entry mistake');

        $this->assertEquals(10800, (float) $row1->fresh()->cl_balance);
        $this->assertEquals(10800, (float) $row2->fresh()->op_balance);
        $this->assertEquals(12000, (float) $row2->fresh()->cl_balance);
        $this->assertEquals(12000, (float) $row3->fresh()->op_balance);
        $this->assertEquals(13300, (float) $row3->fresh()->cl_balance);
        $this->assertEquals(13300, (float) $investment->fresh()->current_balance);
        $this->assertStringContainsString('Revised from $1,000.00 to $800.00', $row1->fresh()->description);
        $this->assertNotNull($row1->fresh()->edited_at);
        $this->assertSame($editor->id, $row1->fresh()->edited_by);
    }

    public function test_revising_a_non_interest_credit_transaction_is_rejected(): void
    {
        $investor = Investor::create(['name' => 'Rejection Investor', 'status' => 'active']);
        $editor = User::first() ?? User::factory()->create();

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $contribution = InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'date' => '2024-01-01',
            'type' => InvestmentTransactionType::contribution->value,
            'debit' => 10000,
            'posted' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        app(InvestmentInterestService::class)->reviseInterestTransaction($contribution, 9000, $editor);
    }
}

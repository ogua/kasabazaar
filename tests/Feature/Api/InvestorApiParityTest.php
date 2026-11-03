<?php

namespace Tests\Feature\Api;

use App\Models\Investment;
use App\Models\InvestmentInterestPayout;
use App\Models\InvestmentWithdrawalRequest;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvestorApiParityTest extends TestCase
{
    use RefreshDatabase;

    private Investor $investor;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        $this->investor = Investor::create(['name' => 'Jane Investor', 'status' => 'active', 'default_annual_rate' => 18]);
        Sanctum::actingAs(User::factory()->create(['investor_id' => $this->investor->id, 'status' => 'active']));
    }

    private function loan(): Investment
    {
        return Investment::create([
            'investor_id' => $this->investor->id,
            'capital_type' => 'loan',
            'payout_frequency' => 'quarterly',
            'principal_amount' => 20000,
            'current_balance' => 20000,
            'start_date' => now()->subMonths(2)->toDateString(),
            'contract_term_months' => 24,
            'status' => 'active',
        ]);
    }

    public function test_investments_show_the_rate_and_payout_terms(): void
    {
        $loan = $this->loan();

        $this->getJson('/api/v1/investor/investments/'.$loan->id)
            ->assertOk()
            ->assertJsonPath('data.investment.payout_frequency', 'quarterly')
            ->assertJsonPath('data.investment.annual_rate', 18)
            ->assertJsonPath('data.investment.rate_source', 'investor_default')
            ->assertJsonPath('data.investment.next_payout_due_date', $loan->fresh()->next_payout_due_date?->toDateString());
    }

    public function test_the_loan_payout_schedule_is_listed(): void
    {
        $loan = $this->loan();
        InvestmentInterestPayout::create([
            'investment_id' => $loan->id,
            'investor_id' => $this->investor->id,
            'period_start' => now()->subMonths(3)->toDateString(),
            'period_end' => now()->subDay()->toDateString(),
            'due_date' => now()->toDateString(),
            'principal_balance' => 20000,
            'rate_applied' => 18,
            'amount' => 900,
            'status' => 'due',
        ]);

        $this->getJson('/api/v1/investor/interest-payouts?investment_id='.$loan->id)
            ->assertOk()
            ->assertJsonPath('data.0.amount', 900)
            ->assertJsonPath('data.0.status', 'due')
            ->assertJsonPath('data.0.investment_reference', $loan->reference);
    }

    public function test_the_portfolio_summary_has_the_dashboard_facts(): void
    {
        $loan = $this->loan();
        InvestmentWithdrawalRequest::create([
            'investor_id' => $this->investor->id,
            'investment_id' => $loan->id,
            'is_full_withdrawal' => true,
            'status' => 'submitted',
            'notice_date' => now()->toDateString(),
            'required_action_by' => now()->addDays(30)->toDateString(),
        ]);

        $this->getJson('/api/v1/investor/portfolio-summary')
            ->assertOk()
            ->assertJsonPath('data.active_count', 1)
            ->assertJsonPath('data.pending_withdrawal_count', 1)
            ->assertJsonPath('data.pending_withdrawal_amount', 20000)
            ->assertJsonPath('data.holdings.0.reference', $loan->reference);

        $this->getJson('/api/v1/investor/withdrawal-requests')
            ->assertOk()
            ->assertJsonPath('data.0.is_principal_repayment', true);
    }
}

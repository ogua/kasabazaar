<?php

namespace Tests\Feature;

use App\Filament\Investor\Widgets\InvestorPortfolioSummaryWidget;
use App\Models\Investment;
use App\Models\InvestmentInterestPayout;
use App\Models\Investor;
use App\Models\User;
use App\Service\InvestorPortfolioSummaryService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class InvestorPortfolioSummaryTest extends TestCase
{
    use DatabaseTransactions;

    private function seedMixedPortfolio(): Investor
    {
        $investor = Investor::create(['name' => 'Mixed Portfolio Investor', 'status' => 'active']);

        // Compounding tranche: $10,000 principal, $12,000 current balance -> $2,000 interest earned.
        Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'current_balance' => 12000,
            'capital_type' => 'investment',
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        // Loan tranche: $5,000 principal (never moves), $300 paid interest, $150 due (unpaid).
        $loan = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'current_balance' => 5000,
            'capital_type' => 'loan',
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        InvestmentInterestPayout::create([
            'investment_id' => $loan->id,
            'investor_id' => $investor->id,
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'due_date' => '2024-02-01',
            'principal_balance' => 5000,
            'rate_applied' => 10,
            'amount' => 300,
            'amount_paid' => 300,
            'status' => 'paid',
        ]);
        InvestmentInterestPayout::create([
            'investment_id' => $loan->id,
            'investor_id' => $investor->id,
            'period_start' => '2024-02-01',
            'period_end' => '2024-02-28',
            'due_date' => '2024-03-01',
            'principal_balance' => 5000,
            'rate_applied' => 10,
            'amount' => 150,
            'amount_paid' => 0,
            'status' => 'due',
        ]);

        return $investor;
    }

    public function test_service_computes_loan_aware_portfolio_totals(): void
    {
        $investor = $this->seedMixedPortfolio();

        $summary = app(InvestorPortfolioSummaryService::class)->compute($investor->id);

        // Principal: 10,000 (investment) + 5,000 (loan) = 15,000.
        $this->assertSame(15000.0, $summary['total_principal']);
        // Current value: 12,000 (compounding balance) + 5,000 (loan principal) + 150 (unpaid interest) = 17,150.
        $this->assertSame(17150.0, $summary['current_portfolio_value']);
        // Interest earned: 2,000 (compounding) + 450 (all loan interest, paid + due) = 2,450.
        $this->assertSame(2450.0, $summary['total_interest_earned']);
    }

    public function test_portfolio_summary_api_matches_the_service(): void
    {
        $investor = $this->seedMixedPortfolio();

        $user = User::create([
            'name' => 'Portfolio Summary User',
            'email' => 'portfolio-summary-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investor->id,
            'status' => 'active',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/investor/portfolio-summary');

        $response->assertOk();
        $response->assertJsonPath('data.total_principal', 15000);
        $response->assertJsonPath('data.current_portfolio_value', 17150);
        $response->assertJsonPath('data.total_interest_earned', 2450);
    }

    public function test_investor_dashboard_still_renders_after_widget_refactor(): void
    {
        $investor = $this->seedMixedPortfolio();

        $user = User::create([
            'name' => 'Dashboard Render User',
            'email' => 'dashboard-render-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investor->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('investor'));

        Livewire::test(InvestorPortfolioSummaryWidget::class)
            ->assertSee('Total Principal Invested')
            ->assertSee('Current Portfolio Value')
            ->assertSee('Total Interest Earned')
            ->assertSee('$15,000.00')
            ->assertSee('$17,150.00')
            ->assertSee('$2,450.00');
    }
}

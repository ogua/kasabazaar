<?php

namespace Tests\Feature\Api;

use App\Enums\InvestmentConversionStatus;
use App\Models\Investment;
use App\Models\InvestmentConversion;
use App\Models\InvestmentRateSetting;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestorConversionApiTest extends TestCase
{
    use DatabaseTransactions;

    private Investor $investor;

    private Investment $investment;

    protected function setUp(): void
    {
        parent::setUp();

        InvestmentRateSetting::updateOrCreate(['year' => now()->year], ['annual_rate' => 10]);

        $this->investor = Investor::create([
            'first_name' => 'Fiifi',
            'other_names' => 'Mensah',
            'status' => 'active',
        ]);

        $this->investment = Investment::create([
            'investor_id' => $this->investor->id,
            'principal_amount' => 10000,
            'current_balance' => 12000,
            'capital_type' => 'investment',
            'start_date' => now()->subYears(2)->toDateString(),
            'contract_term_months' => 12,
            'status' => 'active',
            'last_interest_posted_year' => now()->year,
            'last_interest_posted_through' => now()->toDateString(),
        ]);

        InvestmentTransaction::create([
            'investment_id' => $this->investment->id,
            'investor_id' => $this->investor->id,
            'date' => now()->toDateString(),
            'period_start' => now()->startOfYear()->toDateString(),
            'period_end' => now()->toDateString(),
            'rate_applied' => 10,
            'type' => 'interest_credit',
            'op_balance' => 10000,
            'credit' => 2000,
            'year' => now()->year,
            'posted' => true,
            'posted_at' => now(),
        ]);
    }

    private function authHeader(): array
    {
        $user = User::create([
            'name' => 'Conversion Test User',
            'email' => 'investor-conv-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $this->investor->id,
            'status' => 'active',
        ]);

        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_eligible_lists_convertible_tranches_with_their_settlement_value(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/investor/conversions/eligible?direction=to_loan');

        $response->assertOk();
        $response->assertJsonPath('data.target_capital_type', 'loan');
        $response->assertJsonPath('data.investments.0.reference', $this->investment->reference);
        $response->assertJsonPath('data.investments.0.principal_amount', 10000);
        $response->assertJsonPath('data.investments.0.accrued_interest', 2000);
        $response->assertJsonPath('data.investments.0.settlement_value', 12000);
        $response->assertJsonPath('data.investments.0.is_contract_due', true);
    }

    public function test_eligible_excludes_tranches_already_of_the_target_type(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/investor/conversions/eligible?direction=to_investment');

        $response->assertOk();
        $response->assertJsonCount(0, 'data.investments');
    }

    public function test_quote_previews_the_settlement_without_writing_anything(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/investor/conversions/quote', [
                'sources' => [
                    ['investment_id' => $this->investment->id, 'mode' => 'full'],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.total_amount', 12000);
        $response->assertJsonPath('data.total_principal_rolled', 10000);
        $response->assertJsonPath('data.total_interest_rolled', 2000);

        $this->assertSame(0, InvestmentConversion::where('investor_id', $this->investor->id)->count());
        $this->assertSame('active', $this->investment->fresh()->status->value);
    }

    public function test_quote_splits_principal_from_interest_for_a_principal_only_roll(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/investor/conversions/quote', [
                'sources' => [
                    ['investment_id' => $this->investment->id, 'mode' => 'principal_only'],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.total_amount', 10000);
        $response->assertJsonPath('data.total_paid_out', 2000);
    }

    public function test_store_raises_a_pending_request_and_does_not_execute_it(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/investor/conversions', [
                'direction' => 'to_loan',
                'sources' => [
                    ['investment_id' => $this->investment->id, 'mode' => 'full'],
                ],
                'target_contract_term_months' => 24,
                'target_payout_frequency' => 'quarterly',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'pending_approval');
        $response->assertJsonPath('data.direction', 'to_loan');
        $response->assertJsonPath('data.total_amount', 12000);
        $response->assertJsonPath('data.requested_by_investor', true);

        // Nothing moves until staff approve and execute.
        $this->assertSame('active', $this->investment->fresh()->status->value);
        $this->assertEqualsWithDelta(12000.0, (float) $this->investment->fresh()->current_balance, 0.01);
    }

    public function test_store_requires_a_payout_frequency_when_converting_to_a_loan(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/investor/conversions', [
                'direction' => 'to_loan',
                'sources' => [
                    ['investment_id' => $this->investment->id, 'mode' => 'full'],
                ],
                'target_contract_term_months' => 24,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target_payout_frequency');
    }

    public function test_store_rejects_a_tranche_already_of_the_target_type(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/investor/conversions', [
                'direction' => 'to_investment',
                'sources' => [
                    ['investment_id' => $this->investment->id, 'mode' => 'full'],
                ],
                'target_contract_term_months' => 24,
            ]);

        $response->assertStatus(422);
    }

    public function test_an_investor_cannot_quote_against_another_investors_tranche(): void
    {
        $otherInvestor = Investor::create(['first_name' => 'Ama', 'status' => 'active']);
        $otherInvestment = Investment::create([
            'investor_id' => $otherInvestor->id,
            'principal_amount' => 5000,
            'current_balance' => 5000,
            'capital_type' => 'investment',
            'start_date' => now()->subYears(2)->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/investor/conversions/quote', [
                'sources' => [
                    ['investment_id' => $otherInvestment->id, 'mode' => 'full'],
                ],
            ]);

        $response->assertNotFound();
    }

    public function test_a_pending_request_can_be_cancelled_by_the_investor(): void
    {
        $created = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/investor/conversions', [
                'direction' => 'to_loan',
                'sources' => [
                    ['investment_id' => $this->investment->id, 'mode' => 'full'],
                ],
                'target_contract_term_months' => 24,
                'target_payout_frequency' => 'quarterly',
            ])->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/investor/conversions/{$created}/cancel");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(
            InvestmentConversionStatus::cancelled,
            InvestmentConversion::find($created)->status
        );
    }

    public function test_conversions_index_is_scoped_to_the_authenticated_investor(): void
    {
        $otherInvestor = Investor::create(['first_name' => 'Ama', 'status' => 'active']);
        InvestmentConversion::create([
            'investor_id' => $otherInvestor->id,
            'direction' => 'to_loan',
            'conversion_date' => now()->toDateString(),
            'status' => InvestmentConversionStatus::pending_approval->value,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/investor/conversions');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }
}

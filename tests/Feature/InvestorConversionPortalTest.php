<?php

namespace Tests\Feature;

use App\Enums\InvestmentConversionDirection;
use App\Enums\InvestmentConversionSourceMode;
use App\Enums\InvestmentConversionStatus;
use App\Enums\InvestmentStatus;
use App\Filament\Investor\Resources\InvestmentConversionResource;
use App\Filament\Investor\Resources\InvestmentConversionResource\Pages\CreateInvestmentConversion;
use App\Filament\Investor\Resources\InvestmentConversionResource\Pages\ListInvestmentConversions;
use App\Models\Investment;
use App\Models\InvestmentConversion;
use App\Models\InvestmentRateSetting;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class InvestorConversionPortalTest extends TestCase
{
    use DatabaseTransactions;

    private Investor $investor;

    private User $investorUser;

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

        $this->investorUser = User::create([
            'name' => 'Fiifi Mensah',
            'email' => 'portal-conv-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $this->investor->id,
            'status' => 'active',
        ]);

        $this->investment = $this->maturedInvestment(10000, 12000);

        $this->actingAs($this->investorUser);
        Filament::setCurrentPanel(Filament::getPanel('investor'));
    }

    private function maturedInvestment(float $principal, float $balance): Investment
    {
        $investment = Investment::create([
            'investor_id' => $this->investor->id,
            'principal_amount' => $principal,
            'current_balance' => $balance,
            'capital_type' => 'investment',
            'start_date' => now()->subYears(2)->toDateString(),
            'contract_term_months' => 12,
            'status' => InvestmentStatus::active->value,
            'last_interest_posted_year' => now()->year,
            'last_interest_posted_through' => now()->toDateString(),
        ]);

        InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $this->investor->id,
            'date' => now()->toDateString(),
            'period_start' => now()->startOfYear()->toDateString(),
            'period_end' => now()->toDateString(),
            'rate_applied' => 10,
            'type' => 'interest_credit',
            'op_balance' => $principal,
            'credit' => $balance - $principal,
            'year' => now()->year,
            'posted' => true,
            'posted_at' => now(),
        ]);

        return $investment->fresh();
    }

    public function test_an_investor_can_submit_a_conversion_request_from_the_portal(): void
    {
        Livewire::actingAs($this->investorUser)
            ->test(CreateInvestmentConversion::class)
            ->fillForm([
                'direction' => InvestmentConversionDirection::to_loan->value,
                'source_ids' => [$this->investment->id],
                'mode' => InvestmentConversionSourceMode::full->value,
                'target_contract_term_months' => 24,
                'target_payout_frequency' => 'quarterly',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $conversion = InvestmentConversion::where('investor_id', $this->investor->id)->firstOrFail();

        $this->assertSame(InvestmentConversionStatus::pending_approval, $conversion->status);
        $this->assertTrue($conversion->requested_by_investor);
        $this->assertEqualsWithDelta(12000.00, (float) $conversion->total_amount, 0.01);
        $this->assertEqualsWithDelta(10000.00, (float) $conversion->total_principal_rolled, 0.01);
        $this->assertEqualsWithDelta(2000.00, (float) $conversion->total_interest_rolled, 0.01);

        // Nothing moves until staff approve and execute.
        $this->investment->refresh();
        $this->assertSame(InvestmentStatus::active, $this->investment->status);
        $this->assertEqualsWithDelta(12000.00, (float) $this->investment->current_balance, 0.01);
    }

    public function test_converting_to_a_loan_requires_a_payout_frequency(): void
    {
        Livewire::actingAs($this->investorUser)
            ->test(CreateInvestmentConversion::class)
            ->fillForm([
                'direction' => InvestmentConversionDirection::to_loan->value,
                'source_ids' => [$this->investment->id],
                'mode' => InvestmentConversionSourceMode::full->value,
                'target_contract_term_months' => 24,
                'target_payout_frequency' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['target_payout_frequency']);
    }

    public function test_the_eligible_list_only_offers_holdings_of_the_opposite_type(): void
    {
        $toLoan = InvestmentConversionResource::eligibleOptions(InvestmentConversionDirection::to_loan->value);
        $this->assertArrayHasKey($this->investment->id, $toLoan);

        // The investor holds no loans, so there is nothing to convert into an investment.
        $toInvestment = InvestmentConversionResource::eligibleOptions(InvestmentConversionDirection::to_investment->value);
        $this->assertSame([], $toInvestment);
    }

    public function test_a_converted_holding_is_no_longer_offered_for_conversion(): void
    {
        $this->investment->update(['status' => InvestmentStatus::converted->value]);

        $options = InvestmentConversionResource::eligibleOptions(InvestmentConversionDirection::to_loan->value);

        $this->assertArrayNotHasKey($this->investment->id, $options);
    }

    public function test_an_investor_can_cancel_their_own_pending_request(): void
    {
        $conversion = InvestmentConversion::create([
            'investor_id' => $this->investor->id,
            'direction' => InvestmentConversionDirection::to_loan->value,
            'conversion_date' => now()->toDateString(),
            'status' => InvestmentConversionStatus::pending_approval->value,
            'requested_by_investor' => true,
            'target_contract_term_months' => 24,
        ]);

        Livewire::actingAs($this->investorUser)
            ->test(ListInvestmentConversions::class)
            ->callTableAction('cancel', $conversion)
            ->assertHasNoTableActionErrors();

        $this->assertSame(InvestmentConversionStatus::cancelled, $conversion->fresh()->status);
    }

    public function test_an_executed_request_can_no_longer_be_cancelled(): void
    {
        $conversion = InvestmentConversion::create([
            'investor_id' => $this->investor->id,
            'direction' => InvestmentConversionDirection::to_loan->value,
            'conversion_date' => now()->toDateString(),
            'status' => InvestmentConversionStatus::executed->value,
            'target_contract_term_months' => 24,
        ]);

        Livewire::actingAs($this->investorUser)
            ->test(ListInvestmentConversions::class)
            ->assertTableActionHidden('cancel', $conversion);
    }

    public function test_an_investor_never_sees_another_investors_conversions(): void
    {
        $otherInvestor = Investor::create(['first_name' => 'Ama', 'status' => 'active']);

        $theirs = InvestmentConversion::create([
            'investor_id' => $otherInvestor->id,
            'direction' => InvestmentConversionDirection::to_loan->value,
            'conversion_date' => now()->toDateString(),
            'status' => InvestmentConversionStatus::pending_approval->value,
            'target_contract_term_months' => 24,
        ]);

        $mine = InvestmentConversion::create([
            'investor_id' => $this->investor->id,
            'direction' => InvestmentConversionDirection::to_loan->value,
            'conversion_date' => now()->toDateString(),
            'status' => InvestmentConversionStatus::pending_approval->value,
            'target_contract_term_months' => 24,
        ]);

        Livewire::actingAs($this->investorUser)
            ->test(ListInvestmentConversions::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);

        $this->assertFalse(InvestmentConversionResource::canView($theirs));
        $this->assertTrue(InvestmentConversionResource::canView($mine));
    }
}

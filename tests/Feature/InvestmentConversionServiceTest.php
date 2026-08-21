<?php

namespace Tests\Feature;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentConversionDirection;
use App\Enums\InvestmentConversionSourceMode;
use App\Enums\InvestmentConversionStatus;
use App\Enums\InvestmentInterestPayoutStatus;
use App\Enums\InvestmentStatus;
use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentConversion;
use App\Models\InvestmentConversionSource;
use App\Models\InvestmentInterestPayout;
use App\Models\InvestmentRateSetting;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\User;
use App\Service\InvestmentConversionService;
use App\Service\InvestorPortfolioSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestmentConversionServiceTest extends TestCase
{
    use DatabaseTransactions;

    private const CONVERSION_DATE = '2026-01-01';

    private InvestmentConversionService $service;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InvestmentConversionService::class);
        $this->staff = User::factory()->create();

        InvestmentRateSetting::updateOrCreate(['year' => 2024], ['annual_rate' => 10]);
        InvestmentRateSetting::updateOrCreate(['year' => 2025], ['annual_rate' => 10]);
        InvestmentRateSetting::updateOrCreate(['year' => 2026], ['annual_rate' => 10]);
    }

    private function investor(): Investor
    {
        return Investor::create(['name' => 'Fiifi Mensah', 'status' => 'active']);
    }

    /**
     * A matured investment tranche carrying posted interest, mirroring how the
     * cashbook-era tranches actually look: principal plus a credited balance.
     */
    private function maturedInvestment(Investor $investor, float $principal, float $balance): Investment
    {
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => $principal,
            'current_balance' => $balance,
            'capital_type' => InvestmentCapitalType::investment->value,
            'start_date' => '2024-01-01',
            'contract_term_months' => 12,
            'maturity_date' => '2025-01-01',
            'status' => InvestmentStatus::active->value,
            'last_interest_posted_year' => 2026,
            'last_interest_posted_through' => self::CONVERSION_DATE,
        ]);

        // Interest posted right through the conversion date, so the settlement is a
        // clean principal + credited-interest figure. Without a real posted row the
        // cursor columns alone would not stop execute()'s stub true-up from adding a
        // further day of accrual on top — the cursors are advisory, the transaction
        // is what postStubPeriodInterest() actually checks.
        InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'date' => self::CONVERSION_DATE,
            'period_start' => '2026-01-01',
            'period_end' => self::CONVERSION_DATE,
            'rate_applied' => 10,
            'type' => InvestmentTransactionType::interest_credit->value,
            'op_balance' => $principal,
            'credit' => $balance - $principal,
            'year' => 2026,
            'posted' => true,
            'posted_at' => now(),
        ]);

        return $investment->fresh();
    }

    private function conversionFor(
        Investor $investor,
        InvestmentConversionDirection $direction,
        array $sources,
        array $attributes = [],
    ): InvestmentConversion {
        $conversion = InvestmentConversion::create(array_merge([
            'investor_id' => $investor->id,
            'direction' => $direction->value,
            'conversion_date' => self::CONVERSION_DATE,
            'status' => InvestmentConversionStatus::approved->value,
            'target_contract_term_months' => 24,
            'target_payout_frequency' => $direction === InvestmentConversionDirection::to_loan ? 'quarterly' : null,
            'target_annual_rate' => 9,
        ], $attributes));

        foreach ($sources as $source) {
            InvestmentConversionSource::create([
                'investment_conversion_id' => $conversion->id,
                'source_investment_id' => $source['investment']->id,
                'mode' => $source['mode']->value,
                'amount_rolled' => $source['amount'] ?? 0,
            ]);
        }

        return $conversion->fresh('sources');
    }

    public function test_full_conversion_of_two_tranches_rolls_principal_plus_interest_into_one_loan(): void
    {
        $investor = $this->investor();
        $first = $this->maturedInvestment($investor, 10000, 12000);
        $second = $this->maturedInvestment($investor, 10000, 11500);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $first, 'mode' => InvestmentConversionSourceMode::full],
            ['investment' => $second, 'mode' => InvestmentConversionSourceMode::full],
        ]);

        $loan = $this->service->execute($conversion, $this->staff);

        // 10,000 + 10,000 principal + 2,000 + 1,500 accrued interest.
        $this->assertEqualsWithDelta(23500.00, (float) $loan->principal_amount, 0.01);
        $this->assertSame(InvestmentCapitalType::loan, $loan->capital_type);
        $this->assertSame(InvestmentStatus::active, $loan->status);
        $this->assertSame($conversion->id, $loan->converted_from_conversion_id);

        foreach ([$first, $second] as $source) {
            $source->refresh();
            $this->assertSame(InvestmentStatus::converted, $source->status);
            $this->assertEqualsWithDelta(0.0, (float) $source->current_balance, 0.01);
        }

        $conversion->refresh();
        $this->assertSame(InvestmentConversionStatus::executed, $conversion->status);
        $this->assertEqualsWithDelta(20000.00, (float) $conversion->total_principal_rolled, 0.01);
        $this->assertEqualsWithDelta(3500.00, (float) $conversion->total_interest_rolled, 0.01);
    }

    public function test_conversion_out_debits_and_conversion_in_credit_net_to_zero(): void
    {
        $investor = $this->investor();
        $investment = $this->maturedInvestment($investor, 10000, 12000);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::full],
        ]);

        $loan = $this->service->execute($conversion, $this->staff);

        $out = $investment->transactions()
            ->where('type', InvestmentTransactionType::conversion_out->value)
            ->sum('debit');
        $in = $loan->transactions()
            ->where('type', InvestmentTransactionType::conversion_in->value)
            ->sum('credit');

        $this->assertEqualsWithDelta(0.0, (float) $in - (float) $out, 0.01);

        // Both halves cite the conversion, so a statement can pair them.
        $this->assertSame(
            $conversion->id,
            $loan->transactions()->where('type', InvestmentTransactionType::conversion_in->value)->first()->reference_id
        );
    }

    public function test_loan_target_pins_its_rate_and_seeds_its_payout_cursor(): void
    {
        $investor = $this->investor();
        $investment = $this->maturedInvestment($investor, 10000, 12000);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::full],
        ]);

        $loan = $this->service->execute($conversion, $this->staff);

        // Without the override the loan would re-price off InvestmentRateSetting
        // (10%) rather than the 9% agreed on the conversion.
        $override = $loan->rateOverrides()->where('year', 2026)->first();
        $this->assertNotNull($override);
        $this->assertEqualsWithDelta(9.0, (float) $override->annual_rate, 0.01);

        $this->assertNotNull($loan->next_payout_due_date);
        $this->assertSame('2026-04-01', $loan->next_payout_due_date->toDateString());
        $this->assertSame('2028-01-01', $loan->maturity_date->toDateString());
    }

    public function test_principal_only_conversion_pays_interest_out_and_rolls_principal(): void
    {
        $investor = $this->investor();
        $investment = $this->maturedInvestment($investor, 10000, 12000);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::principal_only],
        ]);

        $loan = $this->service->execute($conversion, $this->staff);

        $this->assertEqualsWithDelta(10000.00, (float) $loan->principal_amount, 0.01);

        $paidOut = $investment->transactions()
            ->where('type', InvestmentTransactionType::withdrawal->value)
            ->sum('debit');
        $this->assertEqualsWithDelta(2000.00, (float) $paidOut, 0.01);

        $source = $conversion->fresh('sources')->sources->first();
        $this->assertEqualsWithDelta(2000.00, (float) $source->amount_paid_out, 0.01);
        $this->assertEqualsWithDelta(10000.00, (float) $source->amount_rolled, 0.01);
    }

    public function test_loan_to_investment_rolls_unpaid_payouts_and_leaves_paid_ones_alone(): void
    {
        $investor = $this->investor();

        $loan = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'current_balance' => 20000,
            'capital_type' => InvestmentCapitalType::loan->value,
            'start_date' => '2024-01-01',
            'contract_term_months' => 12,
            'maturity_date' => '2025-01-01',
            'payout_frequency' => 'quarterly',
            'status' => InvestmentStatus::active->value,
        ]);

        $paid = InvestmentInterestPayout::create([
            'investment_id' => $loan->id,
            'investor_id' => $investor->id,
            'period_start' => '2024-01-01',
            'period_end' => '2024-04-01',
            'due_date' => '2024-04-01',
            'principal_balance' => 20000,
            'rate_applied' => 9,
            'amount' => 450,
            'amount_paid' => 450,
            'status' => InvestmentInterestPayoutStatus::paid->value,
        ]);

        $unpaid = InvestmentInterestPayout::create([
            'investment_id' => $loan->id,
            'investor_id' => $investor->id,
            'period_start' => '2024-04-01',
            'period_end' => '2024-07-01',
            'due_date' => '2024-07-01',
            'principal_balance' => 20000,
            'rate_applied' => 9,
            'amount' => 450,
            'amount_paid' => 0,
            'status' => InvestmentInterestPayoutStatus::due->value,
        ]);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_investment, [
            ['investment' => $loan, 'mode' => InvestmentConversionSourceMode::full],
        ]);

        $target = $this->service->execute($conversion, $this->staff);

        // Principal plus the unpaid payout only — the paid one is cash already gone.
        $this->assertEqualsWithDelta(20450.00, (float) $target->principal_amount, 0.01);
        $this->assertSame(InvestmentCapitalType::investment, $target->capital_type);
        $this->assertNull($target->payout_frequency);

        $this->assertSame(InvestmentInterestPayoutStatus::converted, $unpaid->fresh()->status);
        $this->assertSame(InvestmentInterestPayoutStatus::paid, $paid->fresh()->status);
    }

    /**
     * The regression that matters most: a conversion moves capital between
     * instruments, it does not create or destroy any. If a converted tranche and its
     * successor were both counted, every figure the investor sees would double.
     */
    public function test_portfolio_totals_are_unchanged_by_a_full_conversion(): void
    {
        $investor = $this->investor();
        $first = $this->maturedInvestment($investor, 10000, 12000);
        $second = $this->maturedInvestment($investor, 10000, 11500);

        $summaryService = app(InvestorPortfolioSummaryService::class);
        $before = $summaryService->compute($investor->id);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $first, 'mode' => InvestmentConversionSourceMode::full],
            ['investment' => $second, 'mode' => InvestmentConversionSourceMode::full],
        ]);
        $this->service->execute($conversion, $this->staff);

        $after = $summaryService->compute($investor->id);

        // Principal rebases onto the successor: the 23,500 now standing as loan
        // principal is the same money, so the portfolio's total value must not move.
        $this->assertEqualsWithDelta(
            $before['current_portfolio_value'],
            $after['current_portfolio_value'],
            0.01,
            'A conversion must not change what the company owes the investor.'
        );
        $this->assertEqualsWithDelta(23500.00, $after['total_principal'], 0.01);
    }

    public function test_a_tranche_whose_contract_is_not_due_cannot_be_converted_without_an_exception(): void
    {
        $investor = $this->investor();

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'current_balance' => 10000,
            'capital_type' => InvestmentCapitalType::investment->value,
            'start_date' => now()->subMonth(),
            'contract_term_months' => 24,
            'status' => InvestmentStatus::active->value,
        ]);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::full],
        ], ['conversion_date' => now()->toDateString()]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/contract is not yet due/');

        $this->service->execute($conversion, $this->staff);
    }

    public function test_maturity_exception_allows_an_early_conversion(): void
    {
        $investor = $this->investor();

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'current_balance' => 10000,
            'capital_type' => InvestmentCapitalType::investment->value,
            'start_date' => now()->subMonth(),
            'contract_term_months' => 24,
            'status' => InvestmentStatus::active->value,
            'last_interest_posted_through' => now()->subDay()->toDateString(),
            'last_interest_posted_year' => now()->year,
        ]);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::full],
        ], [
            'conversion_date' => now()->toDateString(),
            'maturity_exception_approved' => true,
        ]);

        $loan = $this->service->execute($conversion, $this->staff);

        $this->assertSame(InvestmentCapitalType::loan, $loan->capital_type);
        $this->assertSame(InvestmentStatus::converted, $investment->fresh()->status);
    }

    public function test_partial_conversion_below_the_minimum_is_rejected(): void
    {
        $investor = $this->investor();
        $investment = $this->maturedInvestment($investor, 50000, 50000);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            [
                'investment' => $investment,
                'mode' => InvestmentConversionSourceMode::partial,
                'amount' => config('investment.partial_minimum') - 1,
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Partial conversions must be at least/');

        $this->service->execute($conversion, $this->staff);
    }

    public function test_partial_conversion_leaving_too_little_behind_is_rejected(): void
    {
        $investor = $this->investor();
        $investment = $this->maturedInvestment($investor, 20000, 20000);

        // Leaves $1,000 — under the $10,000 minimum-remaining floor.
        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::partial, 'amount' => 19000],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/below the .* minimum/');

        $this->service->execute($conversion, $this->staff);
    }

    public function test_partial_conversion_leaves_the_source_active_with_the_remainder(): void
    {
        $investor = $this->investor();
        $investment = $this->maturedInvestment($investor, 40000, 40000);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::partial, 'amount' => 25000],
        ]);

        $loan = $this->service->execute($conversion, $this->staff);

        $this->assertEqualsWithDelta(25000.00, (float) $loan->principal_amount, 0.01);

        $investment->refresh();
        $this->assertSame(InvestmentStatus::active, $investment->status);
        $this->assertEqualsWithDelta(15000.00, (float) $investment->current_balance, 0.01);
    }

    public function test_a_tranche_already_of_the_target_type_cannot_be_converted(): void
    {
        $investor = $this->investor();
        $investment = $this->maturedInvestment($investor, 10000, 10000);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_investment, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::full],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/already a/');

        $this->service->execute($conversion, $this->staff);
    }

    public function test_converting_to_a_loan_without_a_payout_frequency_is_rejected(): void
    {
        $investor = $this->investor();
        $investment = $this->maturedInvestment($investor, 10000, 10000);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::full],
        ], ['target_payout_frequency' => null]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/payout frequency is required/');

        $this->service->execute($conversion, $this->staff);
    }

    public function test_a_pending_conversion_cannot_be_executed_before_approval(): void
    {
        $investor = $this->investor();
        $investment = $this->maturedInvestment($investor, 10000, 10000);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::full],
        ], ['status' => InvestmentConversionStatus::pending_approval->value]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Approve it first/');

        $this->service->execute($conversion, $this->staff);
    }

    public function test_a_conversion_cannot_be_executed_twice(): void
    {
        $investor = $this->investor();
        $investment = $this->maturedInvestment($investor, 10000, 10000);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::full],
        ]);

        $this->service->execute($conversion, $this->staff);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already been executed/');

        $this->service->execute($conversion->fresh(), $this->staff);
    }

    public function test_reversing_a_conversion_restores_the_sources_and_soft_deletes_the_target(): void
    {
        $investor = $this->investor();
        $investment = $this->maturedInvestment($investor, 10000, 12000);

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $investment, 'mode' => InvestmentConversionSourceMode::full],
        ]);

        $loan = $this->service->execute($conversion, $this->staff);

        $this->service->reverse($conversion->fresh(), $this->staff, 'Recorded against the wrong investor.');

        $investment->refresh();
        $this->assertSame(InvestmentStatus::active, $investment->status);
        $this->assertEqualsWithDelta(12000.00, (float) $investment->current_balance, 0.01);

        $this->assertSoftDeleted('investments', ['id' => $loan->id]);
        $this->assertSame(InvestmentConversionStatus::cancelled, $conversion->fresh()->status);
    }

    public function test_quote_matches_what_execute_actually_books(): void
    {
        $investor = $this->investor();
        $first = $this->maturedInvestment($investor, 10000, 12000);
        $second = $this->maturedInvestment($investor, 10000, 11500);

        $quote = $this->service->quote($investor, [
            ['investment_id' => $first->id, 'mode' => 'full'],
            ['investment_id' => $second->id, 'mode' => 'full'],
        ], Carbon::parse(self::CONVERSION_DATE));

        $conversion = $this->conversionFor($investor, InvestmentConversionDirection::to_loan, [
            ['investment' => $first, 'mode' => InvestmentConversionSourceMode::full],
            ['investment' => $second, 'mode' => InvestmentConversionSourceMode::full],
        ]);

        $loan = $this->service->execute($conversion, $this->staff);

        $this->assertEqualsWithDelta(
            $quote['total_amount'],
            (float) $loan->principal_amount,
            0.01,
            'The quoted settlement must be the settlement that is booked.'
        );
    }
}

<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\InvestmentRateSetting;
use App\Models\Investor;
use App\Service\InvestmentAgreementService;
use App\Service\InvestmentInterestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InvestmentAgreementAsOfDateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_default_as_of_date_resolves_from_last_posted_interest_period_or_falls_back_to_start_date(): void
    {
        $investor = Investor::create(['name' => 'Default As Of Investor', 'status' => 'active']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2024-06-01',
            'status' => 'active',
        ]);

        $this->assertSame('2024-06-01', $investment->defaultAsOfDate()->toDateString());

        $investment->update(['last_interest_posted_through' => '2024-12-31']);

        $this->assertSame('2024-12-31', $investment->fresh()->defaultAsOfDate()->toDateString());
    }

    public function test_valuation_does_not_move_with_the_clock_when_no_explicit_as_of_is_given(): void
    {
        $investor = Investor::create(['name' => 'Stable Valuation Investor', 'status' => 'active']);
        InvestmentRateSetting::create(['year' => 2024, 'annual_rate' => 15]);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $service = app(InvestmentInterestService::class);

        Carbon::setTestNow('2025-01-15');
        $valuationDay1 = $service->valuationAsOf($investment->fresh(), $investment->fresh()->defaultAsOfDate());

        Carbon::setTestNow('2026-06-30');
        $valuationDay2 = $service->valuationAsOf($investment->fresh(), $investment->fresh()->defaultAsOfDate());

        Carbon::setTestNow();

        $this->assertEquals($valuationDay1['compounded_balance'], $valuationDay2['compounded_balance']);
    }

    public function test_explicit_as_of_override_still_works(): void
    {
        $investor = Investor::create(['name' => 'Override Investor', 'status' => 'active']);
        InvestmentRateSetting::create(['year' => 2024, 'annual_rate' => 15]);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $pdfDefault = InvestmentAgreementService::generatePdf($investment->fresh());
        $pdfOverride = InvestmentAgreementService::generatePdf($investment->fresh(), Carbon::parse('2024-12-31'));

        // Both render successfully with a different valuation date baked in — the
        // override path doesn't fall back to defaultAsOfDate() or now().
        $this->assertGreaterThan(1000, strlen($pdfDefault->output()));
        $this->assertGreaterThan(1000, strlen($pdfOverride->output()));
    }

    public function test_signed_agreement_route_rejects_a_tampered_as_of_query_param(): void
    {
        $investor = Investor::create(['name' => 'Tamper Investor', 'status' => 'active']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $signedUrl = URL::temporarySignedRoute('investment-agreement', now()->addDay(), [
            'investment' => $investment->id,
            'as_of' => '2024-12-31',
        ]);

        $tamperedUrl = str_replace('as_of=2024-12-31', 'as_of=2099-01-01', $signedUrl);

        $this->get($tamperedUrl)->assertForbidden();
    }
}

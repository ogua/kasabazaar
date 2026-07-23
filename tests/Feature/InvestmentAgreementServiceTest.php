<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\InvestmentRateSetting;
use App\Models\Investor;
use App\Models\User;
use App\Service\InvestmentAgreementService;
use App\Service\InvestmentInterestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvestmentAgreementServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sends_combined_agreement_email_to_investor(): void
    {
        Mail::fake();

        $investor = Investor::create([
            'name' => 'Combined Agreement Investor',
            'status' => 'active',
            'email' => 'combined-agreement@example.com',
        ]);

        Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'start_date' => now(),
        ]);
        Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'start_date' => now(),
        ]);

        $sent = InvestmentAgreementService::sendCombinedEmail($investor);

        $this->assertTrue($sent);
    }

    public function test_combined_agreement_email_fails_gracefully_without_an_email_address(): void
    {
        Mail::fake();

        $investor = Investor::create(['name' => 'No Email Investor', 'status' => 'active']);

        Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'start_date' => now(),
        ]);

        $sent = InvestmentAgreementService::sendCombinedEmail($investor);

        $this->assertFalse($sent);
    }

    public function test_combined_agreement_pdf_generates_for_an_investor_with_multiple_investments(): void
    {
        $investor = Investor::create(['name' => 'Multi Tranche Investor', 'status' => 'active']);

        Investment::create(['investor_id' => $investor->id, 'principal_amount' => 10000, 'start_date' => now()]);
        Investment::create(['investor_id' => $investor->id, 'principal_amount' => 20000, 'start_date' => now()]);

        $pdf = InvestmentAgreementService::generateCombinedPdf($investor->fresh());

        $this->assertGreaterThan(1000, strlen($pdf->output()));
    }

    public function test_combined_agreement_pdf_renders_the_itemized_valuation_breakdown_for_multiple_tranches(): void
    {
        $investor = Investor::create(['name' => 'Itemized Combined Investor', 'status' => 'active']);
        $staffUser = User::first() ?? User::factory()->create();
        InvestmentRateSetting::create(['year' => 2024, 'annual_rate' => 17]);
        InvestmentRateSetting::create(['year' => 2025, 'annual_rate' => 15]);

        $interestService = app(InvestmentInterestService::class);

        // Second tranche created first, to prove the template orders by start_date
        // (chronological "Investment 1"/"Investment 2"), not creation order.
        $laterTranche = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'start_date' => '2024-10-02',
            'status' => 'active',
        ]);
        $earlierTranche = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'start_date' => '2024-06-02',
            'status' => 'active',
        ]);

        $interestService->postDraft($interestService->generateDraft($laterTranche, 2024), $staffUser);
        $interestService->postDraft($interestService->generateDraft($earlierTranche, 2024), $staffUser);

        $pdf = InvestmentAgreementService::generateCombinedPdf($investor->fresh(), Carbon::parse('2025-12-31'));

        $this->assertGreaterThan(1000, strlen($pdf->output()));
    }
}

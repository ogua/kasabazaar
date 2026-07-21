<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\Investor;
use App\Service\InvestmentAgreementService;
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
}

<?php

namespace Tests\Feature\Api;

use App\Models\Investment;
use App\Models\InvestmentInterestPayout;
use App\Models\InvestmentRateSetting;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvestorInvestmentLoanValuationTest extends TestCase
{
    use DatabaseTransactions;

    private function authHeader(Investor $investor): array
    {
        $user = User::create([
            'name' => 'Loan Test User',
            'email' => 'investor-loan-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investor->id,
            'status' => 'active',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_loan_investment_show_returns_payout_based_interest_not_compounding_valuation(): void
    {
        $investor = Investor::create(['name' => 'Loan Interest Investor', 'status' => 'active']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'current_balance' => 10000,
            'capital_type' => 'loan',
            'start_date' => '2024-11-01',
            'status' => 'active',
        ]);

        // Paid: $1,000. Due (unpaid): $800 + $235.62. Skipped: $500 (must be excluded entirely).
        InvestmentInterestPayout::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'period_start' => '2024-11-01',
            'period_end' => '2024-11-30',
            'due_date' => '2024-12-01',
            'principal_balance' => 10000,
            'rate_applied' => 10,
            'amount' => 1000,
            'amount_paid' => 1000,
            'status' => 'paid',
        ]);
        InvestmentInterestPayout::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'period_start' => '2024-12-01',
            'period_end' => '2024-12-31',
            'due_date' => '2025-01-01',
            'principal_balance' => 10000,
            'rate_applied' => 10,
            'amount' => 800,
            'amount_paid' => 0,
            'status' => 'due',
        ]);
        InvestmentInterestPayout::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-31',
            'due_date' => '2025-02-01',
            'principal_balance' => 10000,
            'rate_applied' => 10,
            'amount' => 235.62,
            'amount_paid' => 0,
            'status' => 'processing',
        ]);
        InvestmentInterestPayout::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'period_start' => '2025-02-01',
            'period_end' => '2025-02-28',
            'due_date' => '2025-03-01',
            'principal_balance' => 10000,
            'rate_applied' => 10,
            'amount' => 500,
            'amount_paid' => 0,
            'status' => 'skipped',
        ]);

        $response = $this->withHeaders($this->authHeader($investor))
            ->getJson("/api/v1/investor/investments/{$investment->id}");

        $response->assertOk();
        $response->assertJsonPath('data.valuation.capital_type', 'loan');
        $response->assertJsonPath('data.valuation.interest_earned_total', null);
        $response->assertJsonPath('data.valuation.compounded_balance', null);
        $response->assertJsonPath('data.valuation.interest_accrued_total', 2035.62);
        $response->assertJsonPath('data.valuation.interest_paid_total', 1000);
        $response->assertJsonPath('data.valuation.interest_owed', 1035.62);
        $response->assertJsonPath('data.investment.current_balance', 10000);
        $response->assertJsonPath('data.investment.capital_type', 'loan');
    }

    public function test_investment_type_show_still_returns_compounding_valuation(): void
    {
        InvestmentRateSetting::firstOrCreate(['year' => now()->year], ['annual_rate' => 10]);

        $investor = Investor::create(['name' => 'Compounding Investor', 'status' => 'active']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'current_balance' => 10000,
            'capital_type' => 'investment',
            'start_date' => now(),
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeader($investor))
            ->getJson("/api/v1/investor/investments/{$investment->id}");

        $response->assertOk();
        $response->assertJsonPath('data.valuation.capital_type', 'investment');
        $response->assertJsonPath('data.valuation.interest_accrued_total', null);
        $response->assertJsonPath('data.valuation.interest_paid_total', null);
        $response->assertJsonPath('data.valuation.interest_owed', null);
        $this->assertNotNull($response->json('data.valuation.compounded_balance'));
    }

    public function test_investment_valuation_stays_pinned_to_the_last_posted_period_not_today(): void
    {
        InvestmentRateSetting::firstOrCreate(['year' => now()->year], ['annual_rate' => 17.5]);

        $investor = Investor::create(['name' => 'Pinned Valuation Investor', 'status' => 'active']);

        // Mirrors a real case: principal $10,000, one posted interest_credit
        // covering Nov 2024 - Dec 2025 ($2,035.62 @ 17.5%), current_balance already
        // reflects that credit. last_interest_posted_year (2025) trails "now" by a
        // full year on purpose — this is exactly the gap that used to make
        // valuationAsOf() project an extra, unposted partial-year accrual on top.
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'current_balance' => 12035.62,
            'capital_type' => 'investment',
            'start_date' => '2024-11-01',
            'status' => 'active',
            'last_interest_posted_year' => 2025,
            'last_interest_posted_through' => '2025-12-31',
        ]);

        InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'date' => '2025-12-31',
            'period_start' => '2024-11-01',
            'period_end' => '2025-12-31',
            'type' => 'interest_credit',
            'op_balance' => 10000,
            'debit' => 0,
            'credit' => 2035.62,
            'rate_applied' => 17.5,
            'posted' => true,
            'year' => 2025,
        ]);

        $response = $this->withHeaders($this->authHeader($investor))
            ->getJson("/api/v1/investor/investments/{$investment->id}");

        $response->assertOk();
        $response->assertJsonPath('data.valuation.interest_earned_total', 2035.62);
        $response->assertJsonPath('data.valuation.compounded_balance', 12035.62);
        $response->assertJsonPath('data.valuation.as_of', '2025-12-31');
        $response->assertJsonPath('data.investment.current_balance', 12035.62);
    }

    public function test_investments_list_exposes_capital_type_and_interest_owed(): void
    {
        $investor = Investor::create(['name' => 'List Loan Investor', 'status' => 'active']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'current_balance' => 5000,
            'capital_type' => 'loan',
            'start_date' => now(),
            'status' => 'active',
        ]);

        InvestmentInterestPayout::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'period_start' => now()->subMonth(),
            'period_end' => now(),
            'due_date' => now(),
            'principal_balance' => 5000,
            'rate_applied' => 10,
            'amount' => 41.67,
            'amount_paid' => 0,
            'status' => 'due',
        ]);

        $response = $this->withHeaders($this->authHeader($investor))
            ->getJson('/api/v1/investor/investments');

        $response->assertOk();
        $response->assertJsonPath('data.0.capital_type', 'loan');
        $response->assertJsonPath('data.0.interest_owed', 41.67);
    }

    public function test_investor_can_upload_a_signed_agreement(): void
    {
        Storage::fake('public');

        $investor = Investor::create(['name' => 'Signer Investor', 'status' => 'active']);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'current_balance' => 5000,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf');

        $response = $this->withHeaders($this->authHeader($investor))
            ->post("/api/v1/investor/investments/{$investment->id}/signed-agreement", [
                'signed_agreement' => $file,
                'acknowledged' => true,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.agreement_status', 'pending_review');
        $response->assertJsonPath('data.has_signed_agreement', true);

        $investment->refresh();
        $this->assertSame('pending_review', $investment->agreement_status->value);
        $this->assertNotNull($investment->agreement_signed_at);
        $this->assertNotNull($investment->agreement_acknowledged_ip);
        Storage::disk('public')->assertExists($investment->signed_agreement_path);
    }

    /**
     * The agreement now carries the investor's name already affixed as the signature,
     * so the upload alone is not evidence of assent — the explicit confirmation is.
     */
    public function test_signed_agreement_upload_requires_an_explicit_acknowledgement(): void
    {
        Storage::fake('public');

        $investor = Investor::create(['name' => 'Unacknowledging Investor', 'status' => 'active']);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'current_balance' => 5000,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeader($investor))
            ->postJson("/api/v1/investor/investments/{$investment->id}/signed-agreement", [
                'signed_agreement' => UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('acknowledged');

        $investment->refresh();
        $this->assertSame('unsigned', $investment->agreement_status->value);
        $this->assertNull($investment->signed_agreement_path);
    }

    public function test_signed_agreement_upload_is_rejected_before_investment_is_funded(): void
    {
        $investor = Investor::create(['name' => 'Unfunded Investor', 'status' => 'active']);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'start_date' => now(),
            'status' => 'pending_payment',
        ]);

        $response = $this->withHeaders($this->authHeader($investor))
            ->post("/api/v1/investor/investments/{$investment->id}/signed-agreement", [
                'signed_agreement' => UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(422);
    }

    public function test_signed_agreement_upload_is_rejected_when_already_finalized(): void
    {
        $investor = Investor::create(['name' => 'Finalized Investor', 'status' => 'active']);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'current_balance' => 5000,
            'start_date' => now(),
            'status' => 'active',
            'agreement_status' => 'finalized',
        ]);

        $response = $this->withHeaders($this->authHeader($investor))
            ->post("/api/v1/investor/investments/{$investment->id}/signed-agreement", [
                'signed_agreement' => UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(422);
    }

    public function test_signed_agreement_upload_is_rejected_when_already_pending_review(): void
    {
        $investor = Investor::create(['name' => 'Pending Review Investor', 'status' => 'active']);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'current_balance' => 5000,
            'start_date' => now(),
            'status' => 'active',
            'agreement_status' => 'pending_review',
        ]);

        $response = $this->withHeaders($this->authHeader($investor))
            ->post("/api/v1/investor/investments/{$investment->id}/signed-agreement", [
                'signed_agreement' => UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(422);
    }
}

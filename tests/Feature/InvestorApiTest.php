<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\InvestmentRateSetting;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvestorApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_investor_can_login_and_see_only_their_own_investments(): void
    {
        $investorA = Investor::create(['name' => 'API Investor A', 'status' => 'active']);
        $investorB = Investor::create(['name' => 'API Investor B', 'status' => 'active']);

        $investmentA = Investment::create([
            'investor_id' => $investorA->id,
            'principal_amount' => 8000,
            'current_balance' => 8000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        Investment::create([
            'investor_id' => $investorB->id,
            'principal_amount' => 20000,
            'current_balance' => 20000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $email = 'investor-api-'.uniqid().'@example.com';
        $userA = User::create([
            'name' => 'API Investor A User',
            'email' => $email,
            'password' => bcrypt('password'),
            'investor_id' => $investorA->id,
            'status' => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password',
        ]);

        $loginResponse->assertOk();
        $loginResponse->assertJsonPath('data.investor_id', $investorA->id);
        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/investor/investments');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $investmentA->id);
    }

    public function test_non_investor_user_is_blocked_from_investor_routes(): void
    {
        $staffUser = User::first() ?? User::factory()->create(['password' => bcrypt('password')]);
        $token = $staffUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/investor/investments');

        $response->assertStatus(403);
    }

    public function test_inactive_investor_is_blocked_from_investor_routes(): void
    {
        $investor = Investor::create(['name' => 'Inactive Investor', 'status' => 'inactive']);

        $user = User::create([
            'name' => 'Inactive Investor User',
            'email' => 'investor-inactive-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investor->id,
            'status' => 'active',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/investor/investments');

        $response->assertStatus(403);
    }

    public function test_investor_can_submit_a_withdrawal_request_via_api(): void
    {
        $investor = Investor::create(['name' => 'API Withdrawal Investor', 'status' => 'active']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'current_balance' => 20000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'API Withdrawal User',
            'email' => 'investor-wd-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investor->id,
            'status' => 'active',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/investor/withdrawal-requests', [
                'investment_id' => $investment->id,
                'is_full_withdrawal' => false,
                'requested_amount' => 6000,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'submitted');
        $this->assertEquals(6000, $response->json('data.requested_amount'));
    }

    public function test_withdrawal_request_via_api_is_rejected_before_contract_is_due(): void
    {
        $investor = Investor::create(['name' => 'API Premature Withdrawal Investor', 'status' => 'active']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 20000,
            'current_balance' => 20000,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'API Premature Withdrawal User',
            'email' => 'investor-wd-early-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investor->id,
            'status' => 'active',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/investor/withdrawal-requests', [
                'investment_id' => $investment->id,
                'is_full_withdrawal' => true,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => "This investment's contract is not yet due. Withdrawal requests can be submitted starting {$investment->maturity_date->format('F j, Y')}."]);
    }

    public function test_investor_can_self_serve_invest_via_paystack_checkout(): void
    {
        $investor = Investor::create(['name' => 'Self Serve Investor', 'status' => 'active', 'email' => 'selfserve@example.com']);

        $user = User::create([
            'name' => 'Self Serve User',
            'email' => 'investor-selfserve-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investor->id,
            'status' => 'active',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        // The verify fake is a closure so it can look up whatever investment id
        // store() actually generates, rather than a fixed value known up front —
        // registering two static fakes for the same URL pattern in one test doesn't
        // reliably override the first (the earlier-registered rule kept matching).
        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/selfserve123',
                    'reference' => 'SELF-REF-1',
                ],
            ]),
            '*/transaction/verify/*' => function () use ($investor) {
                $investment = Investment::where('investor_id', $investor->id)->latest('created_at')->first();

                return Http::response([
                    'data' => [
                        'status' => 'success',
                        'metadata' => ['type' => 'investment_deposit', 'investment_id' => $investment->id],
                    ],
                ]);
            },
        ]);

        $storeResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/investor/investments', [
                'principal_amount' => 15000,
                'gateway' => 'paystack',
            ]);

        $storeResponse->assertCreated();
        $storeResponse->assertJsonPath('data.gateway', 'paystack');
        $storeResponse->assertJsonPath('data.reference', 'SELF-REF-1');
        $investmentId = $storeResponse->json('data.investment_id');

        $investment = Investment::findOrFail($investmentId);
        $this->assertSame($investor->id, $investment->investor_id);
        $this->assertSame('pending_payment', $investment->status->value);

        $verifyResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/investor/investments/{$investmentId}/verify", [
                'reference' => 'SELF-REF-1',
            ]);

        $verifyResponse->assertOk();
        $verifyResponse->assertJsonPath('data.status', 'active');
        $this->assertSame('active', $investment->fresh()->status->value);
        $this->assertEquals(1, $investment->fresh()->transactions()->where('type', 'contribution')->count());
    }

    public function test_investor_can_fetch_signed_statement_url_and_download_it(): void
    {
        InvestmentRateSetting::firstOrCreate(['year' => now()->year], ['annual_rate' => 10]);

        $investor = Investor::create(['name' => 'Statement Investor', 'status' => 'active']);

        Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'current_balance' => 10000,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Statement User',
            'email' => 'investor-statement-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investor->id,
            'status' => 'active',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/investor/statement');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.url'));
        $this->assertNotEmpty($response->json('data.download_url'));

        $pdfResponse = $this->get($response->json('data.url'));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_investor_can_preview_the_agreement_for_a_pending_payment_investment(): void
    {
        InvestmentRateSetting::firstOrCreate(['year' => now()->year], ['annual_rate' => 10]);

        $investor = Investor::create(['name' => 'Pending Agreement Investor', 'status' => 'active']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 15000,
            'start_date' => now(),
            'status' => 'pending_payment',
        ]);

        $user = User::create([
            'name' => 'Pending Agreement User',
            'email' => 'investor-pending-agreement-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investor->id,
            'status' => 'active',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/investor/investments/{$investment->id}/agreement");

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.url'));

        $pdfResponse = $this->get($response->json('data.url'));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_self_serve_investment_rejects_gateway_reference_mismatch(): void
    {
        $investorA = Investor::create(['name' => 'Mismatch Investor A', 'status' => 'active']);
        $investorB = Investor::create(['name' => 'Mismatch Investor B', 'status' => 'active']);

        $investmentA = Investment::create([
            'investor_id' => $investorA->id,
            'principal_amount' => 5000,
            'start_date' => now(),
            'deposit_gateway' => 'paystack',
        ]);

        $investmentB = Investment::create([
            'investor_id' => $investorB->id,
            'principal_amount' => 9000,
            'start_date' => now(),
            'deposit_gateway' => 'paystack',
        ]);

        $userA = User::create([
            'name' => 'Mismatch User A',
            'email' => 'investor-mismatch-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investorA->id,
            'status' => 'active',
        ]);

        $token = $userA->createToken('test')->plainTextToken;

        // A reference whose metadata actually points at investment B, mistakenly
        // verified against investment A's route.
        Http::fake([
            '*/transaction/verify/*' => Http::response([
                'data' => [
                    'status' => 'success',
                    'metadata' => ['type' => 'investment_deposit', 'investment_id' => $investmentB->id],
                ],
            ]),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/investor/investments/{$investmentA->id}/verify", [
                'reference' => 'WRONG-REF',
            ]);

        $response->assertStatus(422);
        $this->assertSame('pending_payment', $investmentA->fresh()->status->value);
    }
}

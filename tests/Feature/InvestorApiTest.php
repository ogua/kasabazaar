<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}

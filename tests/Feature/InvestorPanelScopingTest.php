<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestorPanelScopingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_investor_only_sees_their_own_investments_in_the_portal(): void
    {
        $investorA = Investor::create(['name' => 'Investor A', 'status' => 'active']);
        $investorB = Investor::create(['name' => 'Investor B', 'status' => 'active']);

        $investmentA = Investment::create([
            'investor_id' => $investorA->id,
            'principal_amount' => 10000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $investmentB = Investment::create([
            'investor_id' => $investorB->id,
            'principal_amount' => 25000,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $userA = User::create([
            'name' => 'Investor A User',
            'email' => 'investor-a-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investorA->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($userA)->get('/investor/investments');

        $response->assertOk();
        $response->assertSee($investmentA->reference);
        $response->assertDontSee($investmentB->reference);
    }

    public function test_investor_login_redirects_away_from_admin_panel_data(): void
    {
        $investor = Investor::create(['name' => 'Investor Login Test', 'status' => 'active']);

        $user = User::create([
            'name' => 'Investor Login User',
            'email' => 'investor-login-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'investor_id' => $investor->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/investor/login');

        // Already authenticated for the investor panel guard, so login page redirects to dashboard.
        $response->assertStatus(302);
    }
}

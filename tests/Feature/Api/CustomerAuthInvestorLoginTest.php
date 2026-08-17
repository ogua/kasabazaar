<?php

namespace Tests\Feature\Api;

use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthInvestorLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_investor_only_user_can_log_in_via_customer_app(): void
    {
        $investor = Investor::create([
            'name' => 'Jane Investor',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'investor_id' => $investor->id,
        ]);

        $response = $this->postJson('/api/v1/customer/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.account_type', 'investor')
            ->assertJsonPath('data.user.investor_id', $investor->id)
            ->assertJsonPath('data.user.investor.id', $investor->id)
            ->assertJsonPath('data.user.client', null);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_inactive_investor_cannot_log_in_via_customer_app(): void
    {
        $investor = Investor::create([
            'name' => 'Inactive Investor',
            'status' => 'inactive',
        ]);

        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'investor_id' => $investor->id,
        ]);

        $response = $this->postJson('/api/v1/customer/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_without_client_or_investor_cannot_log_in_via_customer_app(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/customer/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }
}

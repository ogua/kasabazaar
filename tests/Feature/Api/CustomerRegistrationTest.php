<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Client;
use App\Models\CustomerSignupVerification;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Mail::fake();
        Http::fake();

        $suffix = Str::random(8);
        $this->branch = Branch::create([
            'name' => 'Signup Branch',
            'slug' => 'signup-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function signup(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'branch_id' => $this->branch->id,
        ], $overrides);
    }

    private function walkInClient(array $attributes = []): Client
    {
        $client = Client::create(array_merge([
            'branch_id' => $this->branch->id,
            'name' => 'Ama Mensah',
            'email' => null,
            'phone' => '024 123 4567',
        ], $attributes));

        Shipment::create([
            'client_id' => $client->id,
            'branch_id' => $this->branch->id,
            'origin_branch_id' => $this->branch->id,
            'destination_branch_id' => $this->branch->id,
            'status' => 'shipped',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'total' => 300,
        ]);

        return $client;
    }

    private function knownCode(string $verificationId): string
    {
        CustomerSignupVerification::whereKey($verificationId)->update(['code_hash' => Hash::make('123456')]);

        return '123456';
    }

    public function test_a_new_customer_gets_an_account_straight_away(): void
    {
        $this->postJson('/api/v1/customer/auth/register', $this->signup(['whatsapp_opt_in' => true]))
            ->assertCreated()
            ->assertJsonPath('data.user.account_type', 'client')
            ->assertJsonPath('data.user.whatsapp_opt_in', true)
            ->assertJsonStructure(['data' => ['token']]);

        $user = User::where('email', 'ama@example.com')->firstOrFail();
        $this->assertSame($this->branch->id, $user->client->branch_id);
    }

    public function test_an_unknown_branch_is_refused(): void
    {
        $this->postJson('/api/v1/customer/auth/register', $this->signup(['branch_id' => (string) Str::uuid()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');
    }

    public function test_a_walk_in_client_is_linked_only_after_the_code_is_entered(): void
    {
        $client = $this->walkInClient();

        $response = $this->postJson('/api/v1/customer/auth/register', $this->signup(['phone' => '+233241234567']))
            ->assertStatus(202)
            ->assertJsonPath('data.requires_verification', true)
            ->assertJsonPath('data.channel', 'sms')
            ->assertJsonPath('data.masked_destination', '••••••4567');

        $this->assertDatabaseMissing('users', ['email' => 'ama@example.com']);

        $verificationId = $response->json('data.verification_id');
        $code = $this->knownCode($verificationId);

        $token = $this->postJson('/api/v1/customer/auth/verify-otp', ['verification_id' => $verificationId, 'code' => $code])
            ->assertCreated()
            ->assertJsonPath('data.user.client_id', $client->id)
            ->json('data.token');

        $this->assertSame(1, Client::count(), 'No duplicate client may be created.');
        $this->assertSame('ama@example.com', $client->fresh()->email);

        $this->withToken($token)->getJson('/api/v1/customer/shipments')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_wrong_code_is_refused_and_the_attempts_are_capped(): void
    {
        $this->walkInClient(['email' => 'ama@example.com', 'phone' => null]);

        $response = $this->postJson('/api/v1/customer/auth/register', $this->signup())
            ->assertStatus(202)
            ->assertJsonPath('data.channel', 'email');

        $verificationId = $response->json('data.verification_id');
        $this->knownCode($verificationId);

        foreach (range(1, 5) as $ignored) {
            $this->postJson('/api/v1/customer/auth/verify-otp', ['verification_id' => $verificationId, 'code' => '000000'])
                ->assertUnprocessable();
        }

        $this->postJson('/api/v1/customer/auth/verify-otp', ['verification_id' => $verificationId, 'code' => '123456'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Too many wrong codes. Request a new one.');

        $this->assertDatabaseMissing('users', ['email' => 'ama@example.com']);
    }

    public function test_an_expired_code_is_refused(): void
    {
        $this->walkInClient();

        $verificationId = $this->postJson('/api/v1/customer/auth/register', $this->signup(['phone' => '0241234567']))
            ->json('data.verification_id');
        $code = $this->knownCode($verificationId);

        $this->travel(11)->minutes();

        $this->postJson('/api/v1/customer/auth/verify-otp', ['verification_id' => $verificationId, 'code' => $code])
            ->assertUnprocessable();
    }

    public function test_a_client_who_already_has_a_login_is_not_offered_for_linking(): void
    {
        $client = $this->walkInClient();
        User::factory()->create(['client_id' => $client->id]);

        $this->postJson('/api/v1/customer/auth/register', $this->signup(['phone' => '+233241234567']))
            ->assertCreated();

        $this->assertSame(2, Client::count());
    }
}

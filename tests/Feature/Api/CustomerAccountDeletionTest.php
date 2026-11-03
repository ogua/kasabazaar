<?php

namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\DeviceToken;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::fake();

        $suffix = Str::random(8);
        $this->branch = Branch::create([
            'name' => 'Deletion Test Branch',
            'slug' => 'deletion-test-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);
    }

    public function test_deleting_a_client_account_keeps_shipments_and_strips_personal_data(): void
    {
        $client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'phone' => '+233201234567',
            'address' => '12 Ring Road',
            'id_type' => 'Ghana Card',
            'id_number' => 'GHA-123',
        ]);

        $shipment = Shipment::create([
            'client_id' => $client->id,
            'branch_id' => $this->branch->id,
            'origin_branch_id' => $this->branch->id,
            'destination_branch_id' => $this->branch->id,
            'status' => 'pending',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'total' => 500,
        ]);

        $user = User::factory()->create([
            'email' => 'ama@example.com',
            'password' => Hash::make('password'),
            'status' => UserStatus::Active,
            'client_id' => $client->id,
            'branch_id' => $this->branch->id,
        ]);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'ExponentPushToken[abc]']);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/customer/auth/account')->assertOk();

        $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'client_id' => $client->id]);

        $client->refresh();
        $this->assertSame('Deleted Client', $client->name);
        $this->assertNull($client->email);
        $this->assertNull($client->phone);
        $this->assertNull($client->address);
        $this->assertNull($client->id_number);

        $user->refresh();
        $this->assertSame(UserStatus::Inactive, $user->status);
        $this->assertNotSame('ama@example.com', $user->email);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseMissing('device_tokens', ['user_id' => $user->id]);
    }

    public function test_a_deleted_client_cannot_log_in_again(): void
    {
        $client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Kofi Boateng',
            'email' => 'kofi@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'kofi@example.com',
            'password' => Hash::make('password'),
            'status' => UserStatus::Active,
            'client_id' => $client->id,
        ]);

        Sanctum::actingAs($user);
        $this->deleteJson('/api/v1/customer/auth/account')->assertOk();

        $this->postJson('/api/v1/customer/auth/login', [
            'email' => 'kofi@example.com',
            'password' => 'password',
        ])->assertStatus(401);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\DeviceToken;
use App\Models\Investor;
use App\Models\ShipmentRequest;
use App\Models\User;
use App\Notifications\ShipmentRequestRejected;
use App\Services\PushNotificationService;
use App\Services\ShipmentRequestApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $suffix = Str::random(8);
        $this->branch = Branch::create([
            'name' => 'Notify Test Branch',
            'slug' => 'notify-test-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);
    }

    private function clientUser(): User
    {
        $client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Ama Mensah',
            'email' => 'ama-'.Str::random(6).'@example.com',
        ]);

        return User::factory()->create([
            'client_id' => $client->id,
            'status' => UserStatus::Active,
        ]);
    }

    public function test_client_can_list_count_and_mark_notifications_read(): void
    {
        Http::fake();
        $user = $this->clientUser();
        $request = ShipmentRequest::create([
            'client_id' => $user->client_id,
            'receivers' => [],
            'pickup_location' => 'Accra',
            'preferred_pickup_at' => now()->addDay(),
            'rejection_reason' => 'Out of area',
        ]);
        $user->notifyNow(new ShipmentRequestRejected($request));
        $user->notifyNow(new ShipmentRequestRejected($request));

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/customer/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'shipment_request_rejected');

        $this->getJson('/api/v1/customer/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.count', 2);

        $id = $user->notifications()->first()->id;
        $this->putJson("/api/v1/customer/notifications/{$id}/read")->assertOk();
        $this->getJson('/api/v1/customer/notifications/unread-count')->assertJsonPath('data.count', 1);

        $this->putJson('/api/v1/customer/notifications/read-all')->assertOk();
        $this->getJson('/api/v1/customer/notifications/unread-count')->assertJsonPath('data.count', 0);
    }

    public function test_investor_can_register_a_device_token(): void
    {
        $investor = Investor::create(['name' => 'Jane Investor', 'status' => 'active']);
        $user = User::factory()->create(['investor_id' => $investor->id, 'status' => UserStatus::Active]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/customer/device-tokens', [
            'token' => 'ExponentPushToken[investor]',
            'platform' => 'android',
        ])->assertOk();

        $this->assertDatabaseHas('device_tokens', ['user_id' => $user->id, 'token' => 'ExponentPushToken[investor]']);
    }

    public function test_push_goes_through_expo_and_prunes_unregistered_tokens(): void
    {
        $user = $this->clientUser();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'ExponentPushToken[good]']);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'ExponentPushToken[gone]']);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'legacy-fcm-token']);

        Http::fake([
            'exp.host/*' => fn (Request $request) => Http::response(['data' => array_map(
                fn (array $message) => $message['to'] === 'ExponentPushToken[gone]'
                    ? ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']]
                    : ['status' => 'ok', 'id' => 'ticket'],
                json_decode($request->body(), true),
            )]),
        ]);

        app(PushNotificationService::class)->sendToUser($user->id, 'Shipped', 'Your shipment left', ['type' => 'shipment_status_updated']);

        Http::assertSent(function (Request $request) {
            $tokens = array_column(json_decode($request->body(), true), 'to');
            sort($tokens);

            return $request->url() === 'https://exp.host/--/api/v2/push/send'
                && $tokens === ['ExponentPushToken[gone]', 'ExponentPushToken[good]'];
        });

        $this->assertDatabaseHas('device_tokens', ['token' => 'ExponentPushToken[good]']);
        $this->assertDatabaseMissing('device_tokens', ['token' => 'ExponentPushToken[gone]']);
    }

    public function test_rejecting_a_request_notifies_the_client(): void
    {
        Http::fake();
        $user = $this->clientUser();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'ExponentPushToken[client]']);
        $staff = User::factory()->create(['status' => UserStatus::Active]);

        $request = ShipmentRequest::create([
            'client_id' => $user->client_id,
            'receivers' => [],
            'pickup_location' => 'Accra',
            'preferred_pickup_at' => now()->addDay(),
        ]);

        app(ShipmentRequestApprovalService::class)->reject($request, 'We do not collect from that area yet.', $staff);

        $this->assertSame('rejected', $request->fresh()->status->value);
        $this->assertSame('shipment_request_rejected', $user->notifications()->first()?->data['type']);
        Http::assertSent(fn (Request $sent) => str_contains($sent->url(), 'exp.host')
            && json_decode($sent->body(), true)[0]['data']['type'] === 'shipment_request_rejected');
    }
}

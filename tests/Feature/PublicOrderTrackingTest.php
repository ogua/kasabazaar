<?php

namespace Tests\Feature;

use App\Enums\EcommerceOrderStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\EcommerceOrder;
use App\Models\OrderDeliveryDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicOrderTrackingTest extends TestCase
{
    use DatabaseTransactions;

    private function makeOrderWithDeliveryDetail(string $phone = '0244000000', string $email = 'track-me@example.com'): EcommerceOrder
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.uniqid().'@example.com',
            'phone' => '0200000000',
            'slug' => 'test-branch-'.uniqid(),
        ]);

        $client = Client::create([
            'branch_id' => $branch->id,
            'name' => 'Test Customer',
            'email' => 'customer-'.uniqid().'@example.com',
        ]);

        $customer = User::create([
            'name' => 'Test Customer',
            'email' => $client->email,
            'password' => bcrypt('password'),
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'role' => 'customer',
            'status' => 'active',
        ]);

        $order = EcommerceOrder::create([
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'subtotal_ghs' => 100,
            'total_ghs' => 100,
            'status' => EcommerceOrderStatus::Paid->value,
        ]);

        OrderDeliveryDetail::create([
            'order_id' => $order->id,
            'full_name' => 'Test Customer',
            'phone' => $phone,
            'email' => $email,
            'country' => 'Ghana',
            'region' => 'Greater Accra',
            'city' => 'Accra',
        ]);

        return $order;
    }

    public function test_guest_can_track_an_order_with_matching_phone(): void
    {
        $order = $this->makeOrderWithDeliveryDetail(phone: '0244123456');

        $response = $this->postJson('/api/v1/marketplace/track-order', [
            'order_number' => $order->order_number,
            'contact' => '0244123456',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.order_number', $order->order_number);
        $response->assertJsonPath('data.status', 'paid');
    }

    public function test_guest_can_track_an_order_with_matching_email(): void
    {
        $order = $this->makeOrderWithDeliveryDetail(email: 'guest-track@example.com');

        $response = $this->postJson('/api/v1/marketplace/track-order', [
            'order_number' => $order->order_number,
            'contact' => 'guest-track@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.order_number', $order->order_number);
    }

    public function test_tracking_fails_with_wrong_contact(): void
    {
        $order = $this->makeOrderWithDeliveryDetail();

        $response = $this->postJson('/api/v1/marketplace/track-order', [
            'order_number' => $order->order_number,
            'contact' => 'not-the-right-contact@example.com',
        ]);

        $response->assertNotFound();
    }

    public function test_tracking_fails_for_unknown_order_number(): void
    {
        $response = $this->postJson('/api/v1/marketplace/track-order', [
            'order_number' => 'KMB-'.Str::upper(Str::random(10)),
            'contact' => 'whoever@example.com',
        ]);

        $response->assertNotFound();
    }
}

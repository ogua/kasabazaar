<?php

namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\PickupSchedule;
use App\Models\Product;
use App\Models\ShipmentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerShipmentRequestTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::fake();

        $suffix = Str::random(8);
        $branch = Branch::create([
            'name' => 'Request Test Branch',
            'slug' => 'request-test-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);

        $client = Client::create([
            'branch_id' => $branch->id,
            'name' => 'Ama Mensah',
            'email' => 'ama-'.$suffix.'@example.com',
            'phone' => '+233201234567',
        ]);

        $this->user = User::factory()->create(['client_id' => $client->id, 'status' => UserStatus::Active]);
        $this->product = Product::create(['name' => 'Barrel (Large) '.$suffix, 'category' => 'general', 'value' => 120]);

        Sanctum::actingAs($this->user);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $items): array
    {
        return [
            'receivers' => [[
                'name' => 'Kofi Boateng',
                'phone' => '+233244000000',
                'country' => 'Ghana',
                'city' => 'Kumasi',
                'items' => $items,
            ]],
            'pickup_location' => '12 Ring Road, Accra',
            'preferred_pickup_at' => now()->addDays(2)->setTime(10, 0)->toIso8601String(),
            'notes' => 'Call on arrival',
        ];
    }

    public function test_item_values_come_from_the_catalogue_not_the_client(): void
    {
        $response = $this->postJson('/api/v1/customer/shipment-requests', $this->payload([
            ['product_id' => $this->product->id, 'description' => 'Cheap barrel', 'quantity' => 2, 'estimated_value' => 1],
            ['description' => 'Old sewing machine', 'details' => 'Singer, boxed', 'quantity' => 1, 'estimated_value' => 5000],
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.is_editable', true)
            ->assertJsonPath('data.items_total', 240);

        $items = ShipmentRequest::firstOrFail()->receivers[0]['items'];

        $this->assertSame($this->product->id, $items[0]['product_id']);
        $this->assertSame($this->product->name, $items[0]['description']);
        $this->assertEquals(120, $items[0]['estimated_value']);

        $this->assertNull($items[1]['product_id']);
        $this->assertSame('Old sewing machine', $items[1]['description']);
        $this->assertSame('Singer, boxed', $items[1]['details']);
        $this->assertNull($items[1]['estimated_value']);
    }

    public function test_an_item_needs_a_product_or_a_name(): void
    {
        $this->postJson('/api/v1/customer/shipment-requests', $this->payload([['quantity' => 1]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receivers.0.items.0.product_id', 'receivers.0.items.0.description']);
    }

    public function test_submitting_books_a_linked_pickup_and_editing_moves_it(): void
    {
        $id = $this->postJson('/api/v1/customer/shipment-requests', $this->payload([
            ['product_id' => $this->product->id, 'quantity' => 1],
        ]))->assertCreated()->json('data.id');

        $schedule = PickupSchedule::where('shipment_request_id', $id)->firstOrFail();
        $this->assertSame('12 Ring Road, Accra', $schedule->pickup_location);

        $edited = $this->payload([['product_id' => $this->product->id, 'quantity' => 3]]);
        $edited['pickup_location'] = '4 Oxford Street, Osu';
        $edited['preferred_pickup_at'] = now()->addDays(5)->setTime(14, 0)->toIso8601String();

        $this->putJson("/api/v1/customer/shipment-requests/{$id}", $edited)
            ->assertOk()
            ->assertJsonPath('data.receivers.0.items.0.product_id', $this->product->id)
            ->assertJsonPath('data.receivers.0.items.0.quantity', 3);

        $schedule->refresh();
        $this->assertSame('4 Oxford Street, Osu', $schedule->pickup_location);
        $this->assertSame('14:00', $schedule->scheduled_at->format('H:i'));
        $this->assertStringContainsString('3 ×', $schedule->items_description);
    }

    public function test_cancelling_marks_the_request_cancelled_and_drops_the_pickup(): void
    {
        $id = $this->postJson('/api/v1/customer/shipment-requests', $this->payload([
            ['product_id' => $this->product->id, 'quantity' => 1],
        ]))->json('data.id');

        $this->deleteJson("/api/v1/customer/shipment-requests/{$id}")->assertOk();

        $this->assertSame('cancelled', ShipmentRequest::findOrFail($id)->status->value);
        $this->assertSame('cancelled', PickupSchedule::where('shipment_request_id', $id)->value('status'));

        $this->getJson('/api/v1/customer/shipment-request-stats')->assertJsonPath('data.cancelled', 1);

        $this->putJson("/api/v1/customer/shipment-requests/{$id}", $this->payload([
            ['product_id' => $this->product->id, 'quantity' => 1],
        ]))->assertUnprocessable();
    }
}

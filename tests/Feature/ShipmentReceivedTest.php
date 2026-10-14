<?php

namespace Tests\Feature;

use App\Filament\Resources\ShipmentResource\Pages\ListShipments;
use App\Models\Branch;
use App\Models\ClearingAgent;
use App\Models\Client;
use App\Models\Shipment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * shipments.is_received drives the final "Received" stage on the public tracking
 * page (kasarose.com/tracking.php). Staff set it with "Mark as Received", and
 * "Record Delivery" sets it whenever a receiver signature is uploaded.
 */
class ShipmentReceivedTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    private ClearingAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::preventStrayRequests();
        Http::fake();
        Storage::fake('public');

        $suffix = Str::random(8);
        $this->branch = Branch::create([
            'name' => 'Received Test Branch',
            'slug' => 'received-test-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);

        $this->agent = ClearingAgent::create(['name' => 'Test Agent', 'is_active' => true]);
    }

    private function makeShipment(array $overrides = []): Shipment
    {
        $client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Ama Mensah',
            'email' => 'ama-'.Str::random(6).'@example.com',
            'phone' => '+233201234567',
        ]);

        return Shipment::create(array_merge([
            'client_id' => $client->id,
            'branch_id' => $this->branch->id,
            'origin_branch_id' => $this->branch->id,
            'destination_branch_id' => $this->branch->id,
            'status' => 'cleared',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'total' => 500,
        ], $overrides));
    }

    private function staff(array $permissions): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        $user->branches()->attach($this->branch);

        return $user;
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs($this->staff(['view_any_shipment', 'view_shipment', 'update_shipment']));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($this->branch);
    }

    // ── Mobile API: POST /api/v1/shipments/{id}/deliveries ──────────────────

    public function test_api_delivery_with_signature_marks_shipment_received(): void
    {
        Sanctum::actingAs($this->staff(['update_shipment']));
        $shipment = $this->makeShipment();

        $this->post("/api/v1/shipments/{$shipment->id}/deliveries", [
            'clearing_agent_id' => $this->agent->id,
            'receiver_signature' => UploadedFile::fake()->image('signature.png'),
        ])->assertCreated();

        $shipment->refresh();
        $this->assertSame('delivered', $shipment->status->value);
        $this->assertTrue($shipment->is_received);
    }

    public function test_api_delivery_without_signature_is_delivered_but_not_received(): void
    {
        Sanctum::actingAs($this->staff(['update_shipment']));
        $shipment = $this->makeShipment();

        $this->postJson("/api/v1/shipments/{$shipment->id}/deliveries", [
            'clearing_agent_id' => $this->agent->id,
        ])->assertCreated();

        $shipment->refresh();
        $this->assertSame('delivered', $shipment->status->value);
        $this->assertFalse($shipment->is_received);
    }

    public function test_api_delivery_can_confirm_receipt_explicitly(): void
    {
        Sanctum::actingAs($this->staff(['update_shipment']));
        $shipment = $this->makeShipment();

        $this->postJson("/api/v1/shipments/{$shipment->id}/deliveries", [
            'clearing_agent_id' => $this->agent->id,
            'is_received' => true,
        ])->assertCreated();

        $this->assertTrue($shipment->fresh()->is_received);
    }

    // ── Admin: Record Delivery and Mark as Received table actions ───────────

    public function test_admin_record_delivery_with_receipt_toggle_marks_received(): void
    {
        $this->actingAsAdmin();
        $shipment = $this->makeShipment();

        Livewire::test(ListShipments::class)
            ->callTableAction('record_delivery', $shipment, data: [
                'clearing_agent_id' => $this->agent->id,
                'delivered_at' => now()->toDateTimeString(),
                'is_received' => true,
            ])
            ->assertHasNoTableActionErrors();

        $shipment->refresh();
        $this->assertSame('delivered', $shipment->status->value);
        $this->assertTrue($shipment->is_received);
    }

    public function test_admin_record_delivery_without_signature_or_toggle_is_not_received(): void
    {
        $this->actingAsAdmin();
        $shipment = $this->makeShipment();

        Livewire::test(ListShipments::class)
            ->callTableAction('record_delivery', $shipment, data: [
                'clearing_agent_id' => $this->agent->id,
                'delivered_at' => now()->toDateTimeString(),
            ])
            ->assertHasNoTableActionErrors();

        $this->assertFalse($shipment->fresh()->is_received);
    }

    public function test_admin_can_mark_and_unmark_received(): void
    {
        $this->actingAsAdmin();
        $shipment = $this->makeShipment(['status' => 'delivered']);

        Livewire::test(ListShipments::class)->callTableAction('toggle_received', $shipment);
        $this->assertTrue($shipment->fresh()->is_received);

        Livewire::test(ListShipments::class)->callTableAction('toggle_received', $shipment->fresh());
        $this->assertFalse($shipment->fresh()->is_received);
    }
}

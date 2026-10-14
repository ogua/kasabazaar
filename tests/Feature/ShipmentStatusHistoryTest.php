<?php

namespace Tests\Feature;

use App\Filament\Resources\ShipmentResource\Pages\ListShipments;
use App\Jobs\BulkContainerShipmentStatusJob;
use App\Models\Branch;
use App\Models\ClearingAgent;
use App\Models\Client;
use App\Models\Shipment;
use App\Models\ShipmentUpdate;
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
 * Every status change must leave a shipment_updates row: that table is the Status
 * History on the public tracking page (kasarose.com/tracking.php). ShipmentObserver
 * writes it, whichever path changed the status.
 */
class ShipmentStatusHistoryTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $origin;

    private Branch $destination;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::preventStrayRequests();
        Http::fake();
        Storage::fake('public');

        $this->origin = $this->makeBranch('Michigan');
        $this->destination = $this->makeBranch('Accra');
    }

    private function makeBranch(string $name): Branch
    {
        $suffix = Str::random(8);

        return Branch::create([
            'name' => $name,
            'slug' => 'history-test-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);
    }

    private function makeShipment(array $overrides = []): Shipment
    {
        $client = Client::create([
            'branch_id' => $this->origin->id,
            'name' => 'Ama Mensah',
            'email' => 'ama-'.Str::random(6).'@example.com',
            'phone' => '+233201234567',
        ]);

        return Shipment::create(array_merge([
            'client_id' => $client->id,
            'branch_id' => $this->origin->id,
            'origin_branch_id' => $this->origin->id,
            'destination_branch_id' => $this->destination->id,
            'status' => 'pickup',
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
        $user->branches()->attach($this->origin);

        return $user;
    }

    private function history(Shipment $shipment)
    {
        return ShipmentUpdate::where('shipment_id', $shipment->id)->orderBy('updated_at')->get();
    }

    public function test_a_plain_status_change_writes_history_at_the_right_branch(): void
    {
        $shipment = $this->makeShipment();

        $shipment->update(['status' => 'shipped']);
        $shipment->update(['status' => 'cleared']);

        $rows = $this->history($shipment);
        $this->assertSame(['shipped', 'cleared'], $rows->pluck('status')->all());
        $this->assertSame('Michigan', $rows[0]->location);     // dispatched from origin
        $this->assertSame('Accra', $rows[1]->location);        // cleared at destination
        $this->assertSame('Customs Cleared', $rows[1]->remarks);
    }

    public function test_saving_without_a_status_change_writes_nothing(): void
    {
        $shipment = $this->makeShipment();

        $shipment->update(['estimated_delivery_date' => now()->addWeek()]);

        $this->assertCount(0, $this->history($shipment));
    }

    public function test_delivering_without_a_date_fills_delivered_at(): void
    {
        $shipment = $this->makeShipment(['status' => 'cleared']);

        $shipment->update(['status' => 'delivered']);

        $this->assertNotNull($shipment->fresh()->delivered_at);
    }

    public function test_update_status_action_writes_exactly_one_row_with_staff_notes(): void
    {
        $this->actingAs($this->staff(['view_any_shipment', 'view_shipment', 'update_shipment']));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($this->origin);
        $shipment = $this->makeShipment();

        Livewire::test(ListShipments::class)
            ->callTableAction('shipping', $shipment, data: [
                'location' => 'Port of Tema',
                'status' => 'cleared',
                'shipped_at' => now()->toDateTimeString(),
                'estimated_delivery_date' => now()->addWeek()->toDateString(),
                'remarks' => 'Cleared customs at Tema',
            ])
            ->assertHasNoTableActionErrors();

        $rows = $this->history($shipment);
        $this->assertCount(1, $rows);
        $this->assertSame('Port of Tema', $rows[0]->location);
        $this->assertSame('Cleared customs at Tema', $rows[0]->remarks);
    }

    public function test_api_record_delivery_logs_delivered_then_received(): void
    {
        Sanctum::actingAs($this->staff(['update_shipment']));
        $agent = ClearingAgent::create(['name' => 'Kwesi Agent', 'is_active' => true]);
        $shipment = $this->makeShipment(['status' => 'cleared']);

        $this->post("/api/v1/shipments/{$shipment->id}/deliveries", [
            'clearing_agent_id' => $agent->id,
            'delivery_notes' => 'Left with the receiver.',
            'receiver_signature' => UploadedFile::fake()->image('signature.png'),
        ])->assertCreated();

        $rows = $this->history($shipment);
        $this->assertEqualsCanonicalizing(['delivered', 'received'], $rows->pluck('status')->all());

        $delivered = $rows->firstWhere('status', 'delivered');
        $this->assertSame('Accra', $delivered->location);
        $this->assertSame('Delivered by Kwesi Agent. Left with the receiver.', $delivered->remarks);
    }

    public function test_bulk_container_job_uses_its_note(): void
    {
        $shipment = $this->makeShipment(['status' => 'shipped', 'container_number' => 987654]);

        (new BulkContainerShipmentStatusJob(987654, 'cleared', 'Container 987654 cleared', false))->handle();

        $rows = $this->history($shipment);
        $this->assertCount(1, $rows);
        $this->assertSame('Container 987654 cleared', $rows[0]->remarks);
    }
}

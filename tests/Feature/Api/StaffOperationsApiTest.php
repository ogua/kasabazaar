<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\ClearingAgent;
use App\Models\Client;
use App\Models\Shipment;
use App\Models\ShipmentMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffOperationsApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::fake();
        Storage::fake('public');

        $suffix = Str::random(8);
        $this->branch = Branch::create([
            'name' => 'Ops Branch',
            'slug' => 'ops-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);

        $this->client = Client::create(['branch_id' => $this->branch->id, 'name' => 'Ama Mensah', 'email' => 'ama-'.$suffix.'@example.com', 'phone' => '+233201234567']);

        $role = Role::firstOrCreate(['name' => 'ops-'.$suffix, 'guard_name' => 'web']);
        foreach (['view_any_shipment', 'view_shipment', 'update_shipment', 'view_client'] as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        $staff = User::factory()->create(['status' => 'active', 'branch_id' => $this->branch->id]);
        $staff->assignRole($role);
        $staff->branches()->attach($this->branch);

        Sanctum::actingAs($staff);
    }

    private function shipment(array $attributes = []): Shipment
    {
        return Shipment::create(array_merge([
            'client_id' => $this->client->id,
            'branch_id' => $this->branch->id,
            'origin_branch_id' => $this->branch->id,
            'destination_branch_id' => $this->branch->id,
            'status' => 'port',
            'container_number' => 42,
            'shipping_reference' => 'CON42-26-C1-001',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'total' => 500,
            'paid' => 200,
        ], $attributes));
    }

    public function test_clearing_a_container_records_the_agent_and_an_audit_row(): void
    {
        $this->shipment();
        $agent = ClearingAgent::create(['name' => 'Kwesi Agent', 'is_active' => true]);

        $this->withHeader('X-Branch-Id', $this->branch->id)
            ->putJson('/api/v1/shipment-containers/42', [
                'is_cleared' => true,
                'clearing_agent_id' => $agent->id,
                'review' => 'Duty paid',
                'notify_clients' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_cleared', true)
            ->assertJsonPath('data.clearing_agent_id', $agent->id);

        $this->assertDatabaseHas('container_clearances', ['container_number' => 42, 'clearing_agent_id' => $agent->id, 'is_cleared' => true]);
    }

    public function test_dashboard_kpis_and_annual_operations(): void
    {
        $this->shipment();

        $this->withHeader('X-Branch-Id', $this->branch->id)
            ->getJson('/api/v1/dashboard/management-kpis?from='.now()->startOfMonth()->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.shipments', 1)
            ->assertJsonPath('data.outstanding_receivables', 300);

        $this->getJson('/api/v1/dashboard/annual-operations')
            ->assertOk()
            ->assertJsonPath('data.year', now()->year)
            ->assertJsonStructure(['data' => ['cars_this_year', 'demurrage_ghs', 'demurrage_usd']]);
    }

    public function test_media_library_lists_branch_media_and_legacy_stages_are_mapped(): void
    {
        $shipment = $this->shipment();

        $this->withHeader('X-Branch-Id', $this->branch->id)
            ->post("/api/v1/shipments/{$shipment->id}/media", [
                'file' => UploadedFile::fake()->image('box.jpg'),
                'stage' => 'Customs',
            ])
            ->assertSuccessful();

        $this->assertSame('in-transit', ShipmentMedia::firstOrFail()->stage);

        $this->withHeader('X-Branch-Id', $this->branch->id)
            ->getJson('/api/v1/media?stage=in-transit')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.shipment.client.name', 'Ama Mensah')
            ->assertJsonPath('data.0.stage_label', 'In Transit');
    }

    public function test_client_detail_has_the_client_card_summary(): void
    {
        $this->shipment();
        $this->shipment(['tracking_number' => Shipment::generateTrackingNumber(), 'total' => 100, 'paid' => 100]);

        $this->withHeader('X-Branch-Id', $this->branch->id)
            ->getJson('/api/v1/clients/'.$this->client->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Ama Mensah')
            ->assertJsonPath('data.summary.shipment_count', 2)
            ->assertJsonPath('data.summary.total_billed', 600)
            ->assertJsonPath('data.summary.outstanding', 300);
    }
}

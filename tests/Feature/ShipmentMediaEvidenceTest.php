<?php

namespace Tests\Feature;

use App\Filament\Resources\ShipmentResource;
use App\Filament\Resources\ShipmentResource\Pages\ListShipments;
use App\Filament\Resources\ShipmentResource\Pages\UploadShipmentEvidence;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Shipment;
use App\Models\ShipmentMedia;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The admin "Media / Evidence" table action: staff upload photos, videos and documents
 * for a shipment, and remove ones uploaded by mistake (record and file).
 */
class ShipmentMediaEvidenceTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::preventStrayRequests();
        Http::fake();
        Storage::fake('public');

        $suffix = Str::random(8);
        $this->branch = Branch::create([
            'name' => 'Media Test Branch',
            'slug' => 'media-test-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);
    }

    private function makeShipment(): Shipment
    {
        $client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Ama Mensah',
            'email' => 'ama-'.Str::random(6).'@example.com',
            'phone' => '+233201234567',
        ]);

        return Shipment::create([
            'client_id' => $client->id,
            'branch_id' => $this->branch->id,
            'origin_branch_id' => 'Michigan',
            'destination_branch_id' => 'Ghana',
            'status' => 'pickup',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'total' => 500,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actingAsStaff(array $permissions): User
    {
        $role = Role::firstOrCreate(['name' => 'media-test-'.Str::random(6), 'guard_name' => 'web']);
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        $user->branches()->attach($this->branch);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($this->branch);

        return $user;
    }

    private function storedMedia(Shipment $shipment, string $fileName, ?User $uploader = null, string $stage = 'pickup'): ShipmentMedia
    {
        $path = 'shipment-media/'.$fileName;
        Storage::disk('public')->put($path, 'content');

        return ShipmentMedia::create([
            'shipment_id' => $shipment->id,
            'type' => ShipmentMedia::typeForPath($path),
            'file_path' => $path,
            'stage' => $stage,
            'uploaded_by' => $uploader?->id,
        ]);
    }

    public function test_type_is_detected_from_the_file_extension(): void
    {
        $this->assertSame('image', ShipmentMedia::typeForPath('shipment-media/box.JPG'));
        $this->assertSame('video', ShipmentMedia::typeForPath('shipment-media/loading.mov'));
        $this->assertSame('video', ShipmentMedia::typeForPath('shipment-media/loading.3gp'));
        $this->assertSame('document', ShipmentMedia::typeForPath('shipment-media/bill-of-lading.pdf'));
        $this->assertSame('document', ShipmentMedia::typeForPath('shipment-media/packing-list.xlsx'));
    }

    public function test_staff_can_upload_photos_videos_and_documents(): void
    {
        $staff = $this->actingAsStaff(['view_any_shipment', 'view_shipment', 'update_shipment']);
        $shipment = $this->makeShipment();

        Livewire::test(ListShipments::class)
            ->callTableAction('media_evidence', $shipment, data: [
                'media_stage' => 'delivery',
                'media_caption' => 'Handed to receiver',
                'media_files' => [
                    UploadedFile::fake()->image('receiver.jpg'),
                    UploadedFile::fake()->create('handover.mp4', 500, 'video/mp4'),
                    UploadedFile::fake()->create('waybill.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Media Updated');

        $media = $shipment->media()->get();
        $this->assertCount(3, $media);
        $this->assertEqualsCanonicalizing(['image', 'video', 'document'], $media->pluck('type')->all());
        $this->assertTrue($media->every(fn (ShipmentMedia $item) => $item->stage === 'delivery'
            && $item->caption === 'Handed to receiver'
            && $item->uploaded_by === $staff->id));
        $media->each(fn (ShipmentMedia $item) => Storage::disk('public')->assertExists($item->file_path));
    }

    public function test_stage_is_required_when_files_are_uploaded(): void
    {
        $this->actingAsStaff(['view_any_shipment', 'view_shipment', 'update_shipment']);
        $shipment = $this->makeShipment();

        Livewire::test(ListShipments::class)
            ->callTableAction('media_evidence', $shipment, data: [
                'media_stage' => null,
                'media_files' => [UploadedFile::fake()->image('box.jpg')],
            ])
            ->assertHasTableActionErrors(['media_stage' => 'required']);

        $this->assertSame(0, $shipment->media()->count());
    }

    public function test_unsupported_file_types_are_rejected(): void
    {
        $this->actingAsStaff(['view_any_shipment', 'view_shipment', 'update_shipment']);
        $shipment = $this->makeShipment();

        Livewire::test(ListShipments::class)
            ->callTableAction('media_evidence', $shipment, data: [
                'media_stage' => 'pickup',
                'media_files' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')],
            ])
            ->assertHasTableActionErrors(['media_files']);

        $this->assertSame(0, $shipment->media()->count());
    }

    public function test_ticked_media_is_deleted_with_its_file_and_the_rest_is_kept(): void
    {
        $this->actingAsStaff(['view_any_shipment', 'view_shipment', 'update_shipment']);
        $shipment = $this->makeShipment();
        $wrongPhoto = $this->storedMedia($shipment, 'wrong.jpg');
        $wrongDocument = $this->storedMedia($shipment, 'wrong.pdf');
        $keeper = $this->storedMedia($shipment, 'right.jpg');

        Livewire::test(ListShipments::class)
            ->callTableAction('media_evidence', $shipment, data: [
                'media_to_delete' => [$wrongPhoto->id, $wrongDocument->id],
            ])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Media Updated');

        $this->assertSame([$keeper->id], $shipment->media()->pluck('id')->all());
        Storage::disk('public')->assertMissing('shipment-media/wrong.jpg');
        Storage::disk('public')->assertMissing('shipment-media/wrong.pdf');
        Storage::disk('public')->assertExists('shipment-media/right.jpg');
    }

    public function test_remove_and_upload_can_happen_in_one_save(): void
    {
        $this->actingAsStaff(['view_any_shipment', 'view_shipment', 'update_shipment']);
        $shipment = $this->makeShipment();
        $wrong = $this->storedMedia($shipment, 'blurry.jpg');

        Livewire::test(ListShipments::class)
            ->callTableAction('media_evidence', $shipment, data: [
                'media_to_delete' => [$wrong->id],
                'media_stage' => 'pickup',
                'media_files' => [UploadedFile::fake()->image('sharp.jpg')],
            ])
            ->assertHasNoTableActionErrors();

        $remaining = $shipment->media()->get();
        $this->assertCount(1, $remaining);
        $this->assertNotSame($wrong->id, $remaining->first()->id);
    }

    public function test_staff_without_update_permission_can_only_remove_their_own_uploads(): void
    {
        $uploader = $this->actingAsStaff(['view_any_shipment', 'view_shipment']);
        $shipment = $this->makeShipment();
        $mine = $this->storedMedia($shipment, 'mine.jpg', $uploader);
        $someoneElses = $this->storedMedia($shipment, 'theirs.jpg', User::factory()->create());

        $this->assertSame([$mine->id], ShipmentResource::removableMedia($shipment)->pluck('id')->all());

        Livewire::test(ListShipments::class)
            ->callTableAction('media_evidence', $shipment, data: [
                'media_to_delete' => [$mine->id, $someoneElses->id],
            ]);

        $this->assertModelMissing($mine);
        $this->assertModelExists($someoneElses);
        Storage::disk('public')->assertExists('shipment-media/theirs.jpg');
    }

    public function test_media_from_another_shipment_cannot_be_removed(): void
    {
        $this->actingAsStaff(['view_any_shipment', 'view_shipment', 'update_shipment']);
        $shipment = $this->makeShipment();
        $other = $this->makeShipment();
        $foreign = $this->storedMedia($other, 'foreign.jpg');

        Livewire::test(ListShipments::class)
            ->callTableAction('media_evidence', $shipment, data: [
                'media_to_delete' => [$foreign->id],
            ]);

        $this->assertModelExists($foreign);
        Storage::disk('public')->assertExists('shipment-media/foreign.jpg');
    }

    public function test_saving_with_nothing_selected_changes_nothing(): void
    {
        $this->actingAsStaff(['view_any_shipment', 'view_shipment', 'update_shipment']);
        $shipment = $this->makeShipment();
        $existing = $this->storedMedia($shipment, 'keep.jpg');

        Livewire::test(ListShipments::class)
            ->callTableAction('media_evidence', $shipment, data: [])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Nothing to save');

        $this->assertModelExists($existing);
    }

    public function test_the_gallery_renders_every_media_type(): void
    {
        $shipment = $this->makeShipment();
        $this->storedMedia($shipment, 'box.jpg');
        $this->storedMedia($shipment, 'loading.mp4', stage: 'in-transit');
        $this->storedMedia($shipment, 'waybill.pdf', stage: 'delivery');

        $html = view('filament.components.shipment-media-gallery', [
            'media' => $shipment->media()->get(),
            'emptyMessage' => 'Nothing yet.',
            'showMeta' => true,
        ])->render();

        $this->assertStringContainsString('<img src="'.asset('storage/shipment-media/box.jpg').'"', $html);
        $this->assertStringContainsString('<video src="'.asset('storage/shipment-media/loading.mp4').'"', $html);
        $this->assertStringContainsString('waybill.pdf', $html);
        $this->assertStringNotContainsString('<img src="'.asset('storage/shipment-media/waybill.pdf').'"', $html);
        $this->assertStringContainsString('In Transit', $html);
    }

    public function test_upload_evidence_page_accepts_documents(): void
    {
        $this->actingAsStaff(['view_any_shipment', 'view_shipment', 'update_shipment']);
        $shipment = $this->makeShipment();

        Livewire::test(UploadShipmentEvidence::class, ['record' => $shipment->getRouteKey()])
            ->fillForm([
                'media_stage' => 'pickup',
                'media_files' => [UploadedFile::fake()->create('invoice.pdf', 50, 'application/pdf')],
            ])
            ->call('uploadEvidence')
            ->assertHasNoFormErrors();

        $this->assertSame(['document'], $shipment->media()->pluck('type')->all());
    }
}

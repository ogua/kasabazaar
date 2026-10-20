<?php

namespace Tests\Feature;

use App\Filament\Pages\MediaEvidence;
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
 * The admin "Media & Evidence" library: a branch-scoped gallery of shipment media
 * with filters, uploads and permission-checked removal.
 */
class MediaEvidencePageTest extends TestCase
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

        $this->branch = $this->makeBranch('Media Library Branch');
    }

    private function makeBranch(string $name): Branch
    {
        $suffix = Str::random(8);

        return Branch::create([
            'name' => $name,
            'slug' => 'media-library-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);
    }

    private function makeShipment(?Branch $branch = null, string $clientName = 'Ama Mensah', ?string $container = null): Shipment
    {
        $branch ??= $this->branch;

        $client = Client::create([
            'branch_id' => $branch->id,
            'name' => $clientName,
            'email' => Str::slug($clientName).'-'.Str::random(6).'@example.com',
            'phone' => '+23320'.random_int(1000000, 9999999),
        ]);

        $shipment = Shipment::create([
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'origin_branch_id' => 'Michigan',
            'destination_branch_id' => 'Ghana',
            'status' => 'pickup',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'total' => 500,
        ]);

        if ($container) {
            $shipment->forceFill(['container_number' => $container])->saveQuietly();
        }

        return $shipment;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actingAsStaff(array $permissions): User
    {
        $role = Role::firstOrCreate(['name' => 'media-library-'.Str::random(6), 'guard_name' => 'web']);
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

    private function storedMedia(Shipment $shipment, string $fileName, ?User $uploader = null, string $stage = 'pickup', ?string $caption = null): ShipmentMedia
    {
        $path = 'shipment-media/'.$fileName;
        Storage::disk('public')->put($path, 'content');

        return ShipmentMedia::create([
            'shipment_id' => $shipment->id,
            'type' => ShipmentMedia::typeForPath($path),
            'file_path' => $path,
            'stage' => $stage,
            'caption' => $caption,
            'uploaded_by' => $uploader?->id,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function visibleIds(\Livewire\Features\SupportTesting\Testable $page): array
    {
        return $page->instance()->getMedia()->getCollection()->pluck('id')->sort()->values()->all();
    }

    /**
     * @param  array<int, ShipmentMedia>  $media
     * @return array<int, string>
     */
    private function ids(array $media): array
    {
        return collect($media)->pluck('id')->sort()->values()->all();
    }

    public function test_staff_without_shipment_access_cannot_open_the_page(): void
    {
        $this->actingAsStaff([]);

        Livewire::test(MediaEvidence::class)->assertForbidden();
    }

    public function test_gallery_lists_only_the_current_branchs_media_with_its_details(): void
    {
        $this->actingAsStaff(['view_any_shipment']);
        $ours = $this->storedMedia($this->makeShipment(clientName: 'Kofi Boateng', container: '49'), 'boxes.jpg', caption: 'Sealed boxes');
        $this->storedMedia($this->makeShipment($this->makeBranch('Other Branch'), 'Elsewhere Client'), 'other.jpg');

        $page = Livewire::test(MediaEvidence::class)
            ->assertOk()
            ->assertSee('Kofi Boateng')
            ->assertSee('CON49')
            ->assertSee('Sealed boxes')
            ->assertDontSee('Elsewhere Client');

        $this->assertSame([$ours->id], $this->visibleIds($page));
    }

    public function test_filters_narrow_the_gallery(): void
    {
        $this->actingAsStaff(['view_any_shipment']);
        $inContainer = $this->makeShipment(clientName: 'Kofi Boateng', container: '49');
        $other = $this->makeShipment(clientName: 'Esi Owusu', container: '50');

        $photo = $this->storedMedia($inContainer, 'pickup.jpg', stage: 'pickup');
        $waybill = $this->storedMedia($inContainer, 'waybill.pdf', stage: 'delivery', caption: 'Signed waybill');
        $video = $this->storedMedia($other, 'loading.mp4', stage: 'in-transit');

        $page = Livewire::test(MediaEvidence::class);
        $this->assertSame($this->ids([$photo, $waybill, $video]), $this->visibleIds($page));

        $this->assertSame($this->ids([$photo, $waybill]), $this->visibleIds($page->set('container', '49')));
        $this->assertSame($this->ids([$waybill]), $this->visibleIds($page->set('stage', 'delivery')));

        $page = Livewire::test(MediaEvidence::class)->set('client', $other->client_id);
        $this->assertSame($this->ids([$video]), $this->visibleIds($page));

        $page = Livewire::test(MediaEvidence::class)->call('setType', 'document');
        $this->assertSame($this->ids([$waybill]), $this->visibleIds($page));
        $this->assertSame(['' => 3, 'image' => 1, 'video' => 1, 'document' => 1], $page->instance()->getTypeCounts());

        $page = Livewire::test(MediaEvidence::class)->set('search', 'signed');
        $this->assertSame($this->ids([$waybill]), $this->visibleIds($page));

        $page = Livewire::test(MediaEvidence::class)->set('search', $other->tracking_number);
        $this->assertSame($this->ids([$video]), $this->visibleIds($page));

        $page = Livewire::test(MediaEvidence::class, ['shipment' => $inContainer->id]);
        $this->assertSame($this->ids([$photo, $waybill]), $this->visibleIds($page));

        $page->call('clearFilters');
        $this->assertSame($this->ids([$photo, $waybill, $video]), $this->visibleIds($page));
    }

    public function test_date_range_filter(): void
    {
        $this->actingAsStaff(['view_any_shipment']);
        $shipment = $this->makeShipment();
        $old = $this->storedMedia($shipment, 'old.jpg');
        $old->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();
        $recent = $this->storedMedia($shipment, 'recent.jpg');

        $page = Livewire::test(MediaEvidence::class)->set('from', now()->subDays(2)->toDateString());
        $this->assertSame([$recent->id], $this->visibleIds($page));

        $page = Livewire::test(MediaEvidence::class)->set('until', now()->subDays(5)->toDateString());
        $this->assertSame([$old->id], $this->visibleIds($page));
    }

    public function test_staff_can_upload_media_to_a_shipment(): void
    {
        $staff = $this->actingAsStaff(['view_any_shipment']);
        $shipment = $this->makeShipment();

        Livewire::test(MediaEvidence::class)
            ->callAction('upload', data: [
                'shipment_id' => $shipment->id,
                'stage' => 'delivery',
                'caption' => 'Handed over',
                'files' => [
                    UploadedFile::fake()->image('receiver.jpg'),
                    UploadedFile::fake()->create('waybill.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Evidence Uploaded');

        $media = $shipment->media()->get();
        $this->assertCount(2, $media);
        $this->assertEqualsCanonicalizing(['image', 'document'], $media->pluck('type')->all());
        $this->assertTrue($media->every(fn (ShipmentMedia $item) => $item->stage === 'delivery'
            && $item->caption === 'Handed over'
            && $item->uploaded_by === $staff->id));
    }

    public function test_upload_requires_a_shipment_from_this_branch_and_files(): void
    {
        $this->actingAsStaff(['view_any_shipment']);
        $foreign = $this->makeShipment($this->makeBranch('Other Branch'));

        Livewire::test(MediaEvidence::class)
            ->callAction('upload', data: [
                'shipment_id' => $foreign->id,
                'stage' => 'pickup',
                'files' => [UploadedFile::fake()->image('box.jpg')],
            ])
            ->assertHasActionErrors(['shipment_id']);

        Livewire::test(MediaEvidence::class)
            ->callAction('upload', data: [
                'shipment_id' => $this->makeShipment()->id,
                'stage' => 'pickup',
                'files' => [],
            ])
            ->assertHasActionErrors(['files' => 'required']);

        $this->assertSame(0, ShipmentMedia::query()->count());
    }

    public function test_staff_who_can_update_shipments_can_remove_any_file(): void
    {
        $this->actingAsStaff(['view_any_shipment', 'update_shipment']);
        $media = $this->storedMedia($this->makeShipment(), 'wrong.jpg');

        Livewire::test(MediaEvidence::class)
            ->callAction('deleteMedia', arguments: ['id' => $media->id])
            ->assertNotified('Media Removed');

        $this->assertModelMissing($media);
        Storage::disk('public')->assertMissing('shipment-media/wrong.jpg');
    }

    public function test_other_staff_can_only_remove_their_own_uploads(): void
    {
        $staff = $this->actingAsStaff(['view_any_shipment']);
        $shipment = $this->makeShipment();
        $mine = $this->storedMedia($shipment, 'mine.jpg', $staff);
        $theirs = $this->storedMedia($shipment, 'theirs.jpg', User::factory()->create());

        Livewire::test(MediaEvidence::class)
            ->callAction('deleteMedia', arguments: ['id' => $theirs->id])
            ->assertNotified('Not Removed');

        $this->assertModelExists($theirs);

        Livewire::test(MediaEvidence::class)
            ->set('selected', [$mine->id, $theirs->id])
            ->callAction('deleteSelected')
            ->assertNotified('Media Removed')
            ->assertSet('selected', []);

        $this->assertModelMissing($mine);
        $this->assertModelExists($theirs);
    }

    public function test_media_of_another_branch_cannot_be_removed(): void
    {
        $this->actingAsStaff(['view_any_shipment', 'update_shipment']);
        $foreign = $this->storedMedia($this->makeShipment($this->makeBranch('Other Branch')), 'foreign.jpg');

        Livewire::test(MediaEvidence::class)
            ->callAction('deleteMedia', arguments: ['id' => $foreign->id])
            ->assertNotified('Not Removed');

        $this->assertModelExists($foreign);
    }

    public function test_select_page_ticks_only_removable_files(): void
    {
        $staff = $this->actingAsStaff(['view_any_shipment']);
        $shipment = $this->makeShipment();
        $mine = $this->storedMedia($shipment, 'mine.jpg', $staff);
        $this->storedMedia($shipment, 'theirs.jpg', User::factory()->create());

        Livewire::test(MediaEvidence::class)
            ->call('selectPage')
            ->assertSet('selected', [$mine->id]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AppPlatform;
use App\Enums\MobileApp;
use App\Filament\Resources\AppVersionResource\Pages\CreateAppVersion;
use App\Filament\Resources\AppVersionResource\Pages\ListAppVersions;
use App\Models\AppVersion;
use App\Models\Branch;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppUpdateCheckTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        AppVersion::query()->delete();
    }

    /**
     * @param  array<string, string>  $query
     */
    private function check(array $query): \Illuminate\Testing\TestResponse
    {
        return $this->getJson('/api/v1/app/update-check?'.http_build_query($query));
    }

    public function test_it_is_public_and_prompts_nothing_without_releases(): void
    {
        $this->check(['platform' => 'android', 'version' => '1.0.7'])
            ->assertOk()
            ->assertJsonPath('data.latest_version', '0.0.0')
            ->assertJsonPath('data.minimum_version', '0.0.0')
            ->assertJsonPath('data.update_available', false)
            ->assertJsonPath('data.force_update', false)
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.app', 'client')
            ->assertJsonPath('data.store_url_android', config('app_updates.store_urls.client.android'));
    }

    public function test_an_unknown_platform_gets_no_policy(): void
    {
        AppVersion::factory()->create(['version' => '1.0.8', 'version_code' => 8]);

        $this->check(['platform' => 'windows', 'version' => '1.0.7'])
            ->assertOk()
            ->assertJsonPath('data.platform', null)
            ->assertJsonPath('data.update_available', false);
    }

    public function test_the_highest_active_version_code_is_served(): void
    {
        AppVersion::factory()->create(['version' => '1.0.8', 'version_code' => 8, 'release_notes' => 'Faster tracking.']);
        AppVersion::factory()->create(['version' => '1.0.7', 'version_code' => 7]);
        AppVersion::factory()->inactive()->create(['version' => '1.0.9', 'version_code' => 9]);

        $this->check(['platform' => 'android', 'version' => '1.0.7'])
            ->assertJsonPath('data.latest_version', '1.0.8')
            ->assertJsonPath('data.update_available', true)
            ->assertJsonPath('data.force_update', false)
            ->assertJsonPath('data.minimum_version', '0.0.0')
            ->assertJsonPath('data.update_message', 'Faster tracking.');
    }

    public function test_releases_do_not_leak_across_platforms_or_apps(): void
    {
        AppVersion::factory()->create(['version' => '1.0.8', 'version_code' => 8, 'platform' => AppPlatform::Ios]);
        AppVersion::factory()->create(['version' => '2.0.0', 'version_code' => 20, 'app' => MobileApp::Staff]);

        $this->check(['platform' => 'android', 'version' => '1.0.7'])
            ->assertJsonPath('data.latest_version', '0.0.0');

        $this->check(['app' => 'staff', 'platform' => 'android', 'version' => '1.0.5'])
            ->assertJsonPath('data.app', 'staff')
            ->assertJsonPath('data.latest_version', '2.0.0');
    }

    public function test_a_forced_release_blocks_only_older_builds(): void
    {
        AppVersion::factory()->forced()->create(['version' => '1.0.8', 'version_code' => 8]);

        $this->check(['platform' => 'android', 'version' => '1.0.7'])
            ->assertJsonPath('data.minimum_version', '1.0.8')
            ->assertJsonPath('data.force_update', true);

        $this->check(['platform' => 'android', 'version' => '1.0.8'])
            ->assertJsonPath('data.update_available', false)
            ->assertJsonPath('data.force_update', false);
    }

    public function test_an_older_forced_release_stays_the_floor_after_a_newer_unforced_one(): void
    {
        AppVersion::factory()->forced()->create(['version' => '1.0.8', 'version_code' => 8]);
        AppVersion::factory()->create(['version' => '1.1.0', 'version_code' => 9]);

        $this->check(['platform' => 'android', 'version' => '1.0.7'])
            ->assertJsonPath('data.latest_version', '1.1.0')
            ->assertJsonPath('data.minimum_version', '1.0.8')
            ->assertJsonPath('data.force_update', true);

        $this->check(['platform' => 'android', 'version' => '1.0.8'])
            ->assertJsonPath('data.update_available', true)
            ->assertJsonPath('data.force_update', false);
    }

    public function test_a_build_below_the_minimum_supported_version_is_blocked(): void
    {
        AppVersion::factory()->create(['version' => '1.1.0', 'version_code' => 9, 'minimum_supported_version' => '1.0.5']);

        $this->check(['platform' => 'android', 'version' => '1.0.4'])->assertJsonPath('data.force_update', true);
        $this->check(['platform' => 'android', 'version' => '1.0.5'])->assertJsonPath('data.force_update', false);
    }

    public function test_the_release_store_url_overrides_the_default(): void
    {
        AppVersion::factory()->create(['version' => '1.0.8', 'version_code' => 8, 'store_url' => 'https://play.google.com/store/apps/details?id=x']);

        $this->check(['platform' => 'android', 'version' => '1.0.7'])
            ->assertJsonPath('data.store_url_android', 'https://play.google.com/store/apps/details?id=x')
            ->assertJsonPath('data.store_url_ios', config('app_updates.store_urls.client.ios'));
    }

    public function test_saving_a_release_refreshes_the_cached_policy(): void
    {
        $this->check(['platform' => 'android', 'version' => '1.0.7'])->assertJsonPath('data.latest_version', '0.0.0');

        $release = AppVersion::factory()->create(['version' => '1.0.8', 'version_code' => 8]);
        $this->check(['platform' => 'android', 'version' => '1.0.7'])->assertJsonPath('data.latest_version', '1.0.8');

        $release->update(['is_active' => false]);
        $this->check(['platform' => 'android', 'version' => '1.0.7'])->assertJsonPath('data.latest_version', '0.0.0');
    }

    public function test_staff_can_publish_a_release_from_the_admin_panel(): void
    {
        $admin = $this->actingAsAdminWithTenant();

        Livewire::test(CreateAppVersion::class)
            ->fillForm([
                'app' => MobileApp::Client->value,
                'platform' => AppPlatform::Ios->value,
                'version' => '1.0.8',
                'version_code' => 8,
                'is_forced_update' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('app_versions', [
            'app' => 'client',
            'platform' => 'ios',
            'version' => '1.0.8',
            'is_forced_update' => true,
            'created_by' => $admin->id,
        ]);
    }

    public function test_the_admin_form_rejects_a_bad_version_and_a_floor_above_the_release(): void
    {
        $this->actingAsAdminWithTenant();

        Livewire::test(CreateAppVersion::class)
            ->fillForm([
                'app' => MobileApp::Client->value,
                'platform' => AppPlatform::Android->value,
                'version' => '1.0',
                'version_code' => 8,
            ])
            ->call('create')
            ->assertHasFormErrors(['version' => 'regex']);

        Livewire::test(CreateAppVersion::class)
            ->fillForm([
                'app' => MobileApp::Client->value,
                'platform' => AppPlatform::Android->value,
                'version' => '1.0.8',
                'version_code' => 8,
                'minimum_supported_version' => '1.0.9',
            ])
            ->call('create')
            ->assertHasFormErrors(['minimum_supported_version']);
    }

    public function test_pulling_a_release_deactivates_it(): void
    {
        $this->actingAsAdminWithTenant();
        $release = AppVersion::factory()->create(['version' => '1.0.8', 'version_code' => 8]);

        Livewire::test(ListAppVersions::class)
            ->assertCanSeeTableRecords([$release])
            ->callTableAction('pull', $release);

        $this->assertFalse($release->fresh()->is_active);
    }

    private function actingAsAdminWithTenant(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        foreach (['view_any_app::version', 'view_app::version', 'create_app::version', 'update_app::version'] as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole($role);

        $branch = Branch::create([
            'name' => 'App Release Test Branch',
            'slug' => 'app-release-test-'.$admin->id,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '123 Test Street',
            'email' => 'branch-'.$admin->id.'@example.com',
            'phone' => '0200000000',
        ]);
        $admin->branches()->attach($branch);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($branch);

        return $admin;
    }
}

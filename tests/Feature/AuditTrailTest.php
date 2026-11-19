<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogResource\Pages\ViewActivityLog;
use App\Jobs\BulkContainerShipmentStatusJob;
use App\Models\Activity;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Investor;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShipmentUpdate;
use App\Models\User;
use App\Services\Audit\AccessChangeRecorder;
use App\Services\Audit\Audit;
use App\Services\Audit\AuditContext;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use LogicException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::preventStrayRequests();
        Http::fake();

        $this->branch = $this->makeBranch('Audit Branch');
    }

    private function makeBranch(string $name): Branch
    {
        $suffix = Str::random(8);

        return Branch::create([
            'name' => $name,
            'slug' => 'audit-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'audit-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function staff(array $permissions = []): User
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        $user = User::factory()->create(['status' => 'active', 'password' => Hash::make('secret-password')]);
        $user->assignRole($role);
        $user->branches()->attach($this->branch);

        return $user;
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
            'origin_branch_id' => 'Michigan',
            'destination_branch_id' => 'Ghana',
            'status' => 'pickup',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'total' => 500,
        ], $overrides));
    }

    private function latestFor(object $subject, ?string $event = null): ?Activity
    {
        return Activity::query()
            ->where('subject_type', $subject::class)
            ->where('subject_id', (string) $subject->getKey())
            ->when($event, fn ($query) => $query->where('event', $event))
            ->latest('id')
            ->first();
    }

    public function test_creating_a_shipment_records_who_and_which_branch(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);

        $shipment = $this->makeShipment();

        $activity = $this->latestFor($shipment, 'created');

        $this->assertNotNull($activity);
        $this->assertSame('shipments', $activity->log_name);
        $this->assertSame((string) $staff->id, (string) $activity->causer_id);
        $this->assertSame((string) $this->branch->id, (string) $activity->branch_id);
        $this->assertSame($shipment->auditLabel(), $activity->subject_label);
        $this->assertSame('500', (string) (int) $activity->properties['attributes']['total']);
    }

    public function test_an_update_records_only_the_changed_fields_old_and_new(): void
    {
        $this->actingAs($this->staff());
        $shipment = $this->makeShipment();

        $shipment->update(['total' => 750, 'description' => $shipment->description]);

        $activity = $this->latestFor($shipment, 'updated');

        $this->assertNotNull($activity);
        $this->assertSame(['total'], array_keys($activity->properties['attributes']));
        $this->assertEquals(500, $activity->properties['old']['total']);
        $this->assertEquals(750, $activity->properties['attributes']['total']);
        $this->assertSame(Activity::RISK_HIGH, $activity->risk, 'A change to the shipment total is high risk.');
    }

    public function test_a_status_change_is_one_named_entry_and_the_history_row_knows_who(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);
        $shipment = $this->makeShipment();

        $shipment->update(['status' => 'warehouse']);

        $entries = Activity::query()->forRecord($shipment)->where('created_at', '>=', now()->subMinute())
            ->whereIn('event', ['updated', 'status_changed'])->get();

        $this->assertCount(1, $entries);
        $this->assertSame('status_changed', $entries->first()->event);
        $this->assertStringContainsString('Arrived at Warehouse', $entries->first()->description);

        $row = ShipmentUpdate::where('shipment_id', $shipment->id)->where('status', 'warehouse')->first();
        $this->assertSame((string) $staff->id, (string) $row->recorded_by);
    }

    public function test_a_payment_appears_in_its_shipments_history(): void
    {
        $this->actingAs($this->staff());
        $shipment = $this->makeShipment();

        $payment = Payment::create([
            'branch_id' => $this->branch->id,
            'shipment_id' => $shipment->id,
            'payment_ref' => 'PAY-'.Str::random(6),
            'currency' => 'USD',
            'amount_usd' => 100,
            'paying_method' => 'cash',
        ]);

        $created = $this->latestFor($payment, 'created');
        $this->assertSame(Shipment::class, $created->scope_type);
        $this->assertSame((string) $shipment->id, $created->scope_id);

        $this->assertTrue($shipment->auditTrail()->where('subject_type', Payment::class)->exists());
    }

    public function test_a_password_change_is_recorded_without_the_password(): void
    {
        $user = $this->staff();
        $this->actingAs($user);

        $user->update(['password' => Hash::make('a-new-password')]);

        $activity = $this->latestFor($user, 'password_changed');

        $this->assertNotNull($activity);
        $this->assertSame(Activity::RISK_HIGH, $activity->risk);
        $this->assertTrue($activity->properties['by_owner']);
        $this->assertFalse(
            Activity::query()->where('properties', 'like', '%a-new-password%')->orWhere('properties', 'like', '%"password"%')->exists(),
            'Password values never reach the trail.'
        );
    }

    public function test_investor_bank_details_are_masked_and_flagged(): void
    {
        $this->actingAs($this->staff());
        $investor = Investor::create(['name' => 'Kofi Boateng', 'status' => 'active', 'account_number' => '1234567890']);

        $investor->update(['account_number' => '9876543210']);

        $activity = $this->latestFor($investor, 'updated');

        $this->assertSame('••••3210', $activity->properties['attributes']['account_number']);
        $this->assertSame('••••7890', $activity->properties['old']['account_number']);
        $this->assertSame(Activity::RISK_HIGH, $activity->risk);
    }

    public function test_secret_settings_are_redacted(): void
    {
        $this->actingAs($this->staff());

        $setting = Setting::create(['key' => 'audit_test_api_key', 'value' => 'super-secret-value', 'group' => 'sms']);
        Setting::create(['key' => 'audit_test_sender', 'value' => 'KASAROSE', 'group' => 'sms']);

        $secret = $this->latestFor($setting, 'created');
        $this->assertSame('[redacted]', $secret->properties['attributes']['value']);
        $this->assertSame(Activity::RISK_HIGH, $secret->risk);

        $plain = Activity::query()->where('subject_type', Setting::class)->where('subject_id', 'audit_test_sender')->first();
        $this->assertSame('KASAROSE', $plain->properties['attributes']['value']);
    }

    public function test_audit_entries_cannot_be_changed(): void
    {
        $activity = Audit::event(null, 'test_event', 'A test entry');

        $this->expectException(LogicException::class);
        $activity->update(['description' => 'Tampered']);
    }

    public function test_audit_entries_cannot_be_deleted(): void
    {
        $activity = Audit::event(null, 'test_event', 'A test entry');

        $this->expectException(LogicException::class);
        $activity->delete();
    }

    public function test_audit_as_names_the_entry_and_a_no_op_save_still_records_it(): void
    {
        $this->actingAs($this->staff());
        $branch = $this->makeBranch('Renamed Branch');

        $branch->auditAs('reviewed', 'Branch reviewed', ['note' => 'checked'])->update(['address' => '2 New Street']);
        $named = $this->latestFor($branch);
        $this->assertSame('reviewed', $named->event);
        $this->assertSame('checked', $named->properties['note']);
        $this->assertSame('2 New Street', $named->properties['attributes']['address']);
        $this->assertSame(0, Activity::query()->where('subject_id', (string) $branch->id)->where('event', 'updated')->count());

        $branch->auditAs('confirmed', 'Branch confirmed')->save();
        $this->assertSame('confirmed', $this->latestFor($branch)->event);
    }

    public function test_web_login_and_failed_login_are_recorded_without_the_password(): void
    {
        $user = $this->staff();

        Auth::attempt(['email' => $user->email, 'password' => 'wrong-password']);
        $failed = Activity::query()->where('event', 'login_failed')->latest('id')->first();
        $this->assertSame($user->email, $failed->properties['attempted']);
        $this->assertSame('wrong_password', $failed->properties['reason']);
        $this->assertStringNotContainsString('wrong-password', json_encode($failed->properties));

        Auth::attempt(['email' => $user->email, 'password' => 'secret-password']);
        $login = $this->latestFor($user, 'login');
        $this->assertNotNull($login);
        $this->assertSame((string) $user->id, (string) $login->causer_id);
        $this->assertSame('auth', $login->log_name);
    }

    public function test_staff_api_login_is_recorded_on_the_api_channel(): void
    {
        $user = $this->staff();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'nope'])->assertStatus(401);
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-password'])->assertOk();

        $failed = Activity::query()->where('event', 'login_failed')->latest('id')->first();
        $this->assertSame('api', $failed->channel);
        $this->assertNotNull($failed->ip_address);

        $logins = Activity::query()->where('subject_id', (string) $user->id)->where('event', 'login')->get();
        $this->assertCount(1, $logins, 'One sign-in, not one from the guard and one from the controller.');
        $this->assertSame('api', $logins->first()->channel);
    }

    public function test_inactive_staff_api_login_is_one_refusal_entry(): void
    {
        $user = $this->staff();
        $user->update(['status' => 'inactive']);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-password'])->assertStatus(401);

        $events = Activity::query()->where('subject_id', (string) $user->id)->whereIn('event', ['login', 'logout', 'login_failed', 'login_blocked'])->pluck('event')->all();
        $this->assertSame(['login_blocked'], $events);
    }

    public function test_impersonation_is_recorded_with_the_real_staff_member(): void
    {
        $admin = $this->staff();
        $target = User::factory()->create(['status' => 'active']);
        $this->actingAs($admin);

        $this->get(URL::temporarySignedRoute('impersonate.start', now()->addMinute(), ['user' => $target->id]))->assertRedirect();

        $started = $this->latestFor($target, 'impersonation_started');
        $this->assertSame((string) $admin->id, (string) $started->causer_id);
        $this->assertSame((string) $admin->id, (string) $started->impersonator_id);
        $this->assertFalse(Activity::query()->where('subject_id', (string) $target->id)->where('event', 'login')->exists());

        // Something done while impersonating is the target's action, made by the admin.
        $this->get('/impersonate/stop')->assertRedirect();
        $stopped = $this->latestFor($target, 'impersonation_stopped');
        $this->assertSame((string) $admin->id, (string) $stopped->impersonator_id);
    }

    public function test_a_queued_job_is_attributed_to_the_user_who_queued_it(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);
        $shipment = $this->makeShipment(['status' => 'shipped', 'container_number' => 445566]);

        BulkContainerShipmentStatusJob::dispatch(445566, 'port', 'Container arrived', false)->onConnection('database');

        Auth::logout();
        $this->app['auth']->forgetGuards();
        $this->assertNull(auth()->user());

        $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--stop-when-empty' => true])->assertSuccessful();

        $entry = $this->latestFor($shipment, 'status_changed');
        $this->assertNotNull($entry);
        $this->assertSame((string) $staff->id, (string) $entry->causer_id);
        $this->assertSame('queue', $entry->channel);
        $this->assertSame('BulkContainerShipmentStatusJob', $entry->source);

        $row = ShipmentUpdate::where('shipment_id', $shipment->id)->where('status', 'port')->first();
        $this->assertSame((string) $staff->id, (string) $row->recorded_by);

        $this->assertNull(AuditContext::instance()->backgroundCauserId(), 'The job context is cleared afterwards.');
    }

    public function test_role_permission_changes_are_diffed(): void
    {
        $this->actingAs($this->staff());
        $first = Permission::firstOrCreate(['name' => 'audit_test_one', 'guard_name' => 'web']);
        $second = Permission::firstOrCreate(['name' => 'audit_test_two', 'guard_name' => 'web']);

        $role = Role::create(['name' => 'audit-role-'.Str::random(5), 'guard_name' => 'web']);
        $role->syncPermissions([$first]);

        $role->save();
        $role->syncPermissions([$second]);

        $entry = $this->latestFor($role, 'permissions_changed');
        $this->assertSame(['audit_test_two'], $entry->properties['added']);
        $this->assertSame(['audit_test_one'], $entry->properties['removed']);
    }

    public function test_user_role_and_branch_changes_are_one_summary_each(): void
    {
        $this->actingAs($this->staff());
        $user = User::factory()->create(['status' => 'active']);
        $other = $this->makeBranch('Second Branch');

        $before = AccessChangeRecorder::snapshot($user);
        $user->branches()->sync([$other->id]);
        AccessChangeRecorder::record($user, $before);

        $branches = $this->latestFor($user, 'branches_changed');
        $this->assertSame(['Second Branch'], $branches->properties['added']);

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'audit-summary-role', 'guard_name' => 'web']);
        AccessChangeRecorder::around($user, fn () => $user->assignRole($role));

        // One summary from the recorder; the per-grant package event stays quiet meanwhile.
        $this->assertSame(1, Activity::query()->where('subject_id', (string) $user->id)->where('event', 'roles_changed')->count());
        $this->assertSame(0, Activity::query()->where('subject_id', (string) $user->id)->where('event', 'roles_assigned')->count());
    }

    public function test_opening_a_shipment_document_is_recorded(): void
    {
        $this->actingAs($this->staff());
        $shipment = $this->makeShipment();

        $this->get(route('packing-slip', $shipment->id));

        $download = $this->latestFor($shipment, 'downloaded');
        $this->assertNotNull($download);
        $this->assertSame('exports', $download->log_name);
        $this->assertSame('packing-slip', $download->properties['route']);
    }

    public function test_migrations_do_not_write_audit_entries(): void
    {
        $before = Activity::query()->count();

        event(new \Illuminate\Database\Events\MigrationsStarted('up'));
        $this->makeBranch('Created During Migration');
        event(new \Illuminate\Database\Events\MigrationsEnded('up'));

        $this->assertSame($before, Activity::query()->count());

        $this->makeBranch('Created After');
        $this->assertSame($before + 1, Activity::query()->count());
    }

    public function test_the_history_panel_renders_a_records_trail(): void
    {
        $this->actingAs($this->staff());
        $shipment = $this->makeShipment();
        $shipment->update(['status' => 'warehouse']);

        $html = view('filament.audit.history', [
            'activities' => \App\Filament\Actions\AuditHistoryAction::activitiesFor($shipment),
            'limit' => \App\Filament\Actions\AuditHistoryAction::LIMIT,
        ])->render();

        $this->assertStringContainsString('Status Changed', $html);
        $this->assertStringContainsString('Arrived at Warehouse', $html);
        $this->assertStringContainsString('Created', $html);
    }

    public function test_the_activity_log_needs_permission(): void
    {
        $this->actingAs($this->staff());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($this->branch);

        Livewire::test(ListActivityLogs::class)->assertForbidden();
    }

    public function test_the_activity_log_lists_filters_and_shows_an_entry(): void
    {
        $staff = $this->staff(['view_any_activity::log', 'view_activity::log']);
        $this->actingAs($staff);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($this->branch);

        $shipment = $this->makeShipment();
        $shipment->update(['total' => 900]);
        $entry = $this->latestFor($shipment, 'updated');
        $login = Audit::security('login', 'Someone signed in');

        Livewire::test(ListActivityLogs::class)
            ->assertCanSeeTableRecords([$entry, $login])
            ->filterTable('high_risk', true)
            ->assertCanSeeTableRecords([$entry])
            ->assertCanNotSeeTableRecords([$login]);

        Livewire::test(ViewActivityLog::class, ['record' => $entry->getKey()])
            ->assertOk()
            ->assertSee('Total');

        $this->assertFalse(ActivityLogResource::canCreate());
        $this->assertFalse(ActivityLogResource::canEdit($entry));
        $this->assertFalse(ActivityLogResource::canDelete($entry));
    }
}

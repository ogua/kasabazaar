<?php

namespace Tests\Feature;

use App\Filament\Pages\ImpersonateUser;
use App\Filament\Resources\InvestorResource\Pages\ListInvestors;
use App\Models\Branch;
use App\Models\Investor;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use DatabaseTransactions;

    private function superAdmin(): User
    {
        // assignRole() requires a role guarded to match the model's default
        // guard (web, per config/auth.php) — distinct from the 'sanctum'-guarded
        // role PermissionSeeder creates for API authorization checks.
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Resources on the admin panel are tenanted by Branch — table rows can't
     * render (ViewAction/EditAction URLs need a {tenant}) without one set.
     */
    private function actingAsAdminWithTenant(User $admin): void
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'slug' => 'test-branch-'.$admin->id,
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
    }

    public function test_super_admin_can_impersonate_an_investor_user_and_lands_on_investor_panel(): void
    {
        $admin = $this->superAdmin();

        $investor = Investor::create(['name' => 'Impersonation Target Investor', 'status' => 'active']);
        $investorUser = User::factory()->create(['status' => 'active', 'investor_id' => $investor->id]);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // The Livewire action itself only redirects to a signed controller URL —
        // it does not perform the Auth swap inline (see ImpersonationController::start()
        // for why: a Livewire AJAX response cannot reliably be trusted to hand a
        // mid-request session-ID regeneration back to the browser before the
        // follow-up full-page navigation to the target panel).
        $testable = Livewire::test(ImpersonateUser::class)
            ->callTableAction('impersonate', $investorUser)
            ->assertRedirectContains('/impersonate/start/'.$investorUser->id);

        $startUrl = $testable->effects['redirect'];

        // The auth swap only actually happens once that signed URL is visited —
        // a real, plain, synchronous request.
        $this->get($startUrl)->assertRedirect('/investor');

        $this->assertSame($investorUser->id, auth()->id());
        $this->assertSame($admin->id, session('impersonate.original_id'));
    }

    public function test_impersonate_start_link_rejects_a_tampered_signature(): void
    {
        $admin = $this->superAdmin();

        $investor = Investor::create(['name' => 'Tamper Target Investor', 'status' => 'active']);
        $investorUser = User::factory()->create(['status' => 'active', 'investor_id' => $investor->id]);

        $this->actingAs($admin);

        $otherUser = User::factory()->create();
        $validUrl = \App\Service\ImpersonationService::startUrl($investorUser);
        $tamperedUrl = str_replace($investorUser->id, $otherUser->id, $validUrl);

        $this->get($tamperedUrl)->assertForbidden();
        $this->assertSame($admin->id, auth()->id());
    }

    public function test_impersonated_session_cannot_start_a_further_impersonation(): void
    {
        $admin = $this->superAdmin();
        $anotherAdmin = $this->superAdmin();

        $this->actingAs($admin);
        $this->withSession(['impersonate.original_id' => $anotherAdmin->id, 'impersonate.original_panel' => 'admin']);

        $this->assertFalse(ImpersonateUser::canAccess());
    }

    public function test_stop_impersonating_restores_the_original_admin_and_returns_to_original_panel(): void
    {
        $admin = $this->superAdmin();
        $staffUser = User::factory()->create(['status' => 'active']);

        $this->actingAs($staffUser);

        $response = $this
            ->withSession([
                'impersonate.original_id' => $admin->id,
                'impersonate.original_panel' => 'admin',
            ])
            ->get('/impersonate/stop');

        $response->assertRedirect('/admin');
        $this->assertSame($admin->id, auth()->id());
        $this->assertFalse(session()->has('impersonate.original_id'));
    }

    public function test_stop_impersonating_without_an_active_impersonation_session_is_a_404(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get('/impersonate/stop')
            ->assertNotFound();
    }

    public function test_non_super_admin_cannot_access_the_impersonate_page(): void
    {
        $staffUser = User::factory()->create(['status' => 'active']);

        $this->actingAs($staffUser);

        $this->assertFalse(ImpersonateUser::canAccess());
    }

    public function test_admin_can_impersonate_investor_directly_from_investor_row(): void
    {
        $admin = $this->superAdmin();

        $investor = Investor::create(['name' => 'Row Action Investor', 'status' => 'active']);
        $investorUser = User::factory()->create(['status' => 'active', 'investor_id' => $investor->id]);

        $this->actingAsAdminWithTenant($admin);

        $testable = Livewire::test(ListInvestors::class)
            ->callTableAction('impersonate', $investor)
            ->assertRedirectContains('/impersonate/start/'.$investorUser->id);

        $this->get($testable->effects['redirect'])->assertRedirect('/investor');

        $this->assertSame($investorUser->id, auth()->id());
        $this->assertSame($admin->id, session('impersonate.original_id'));
    }

    public function test_impersonate_row_action_is_disabled_when_investor_has_no_portal_login(): void
    {
        $admin = $this->superAdmin();

        $investor = Investor::create(['name' => 'No Login Investor', 'status' => 'active']);

        $this->actingAsAdminWithTenant($admin);

        Livewire::test(ListInvestors::class)
            ->assertTableActionDisabled('impersonate', $investor);
    }

    public function test_impersonate_row_action_prompts_for_a_choice_with_multiple_portal_logins(): void
    {
        $admin = $this->superAdmin();

        $investor = Investor::create(['name' => 'Multi Login Investor', 'status' => 'active']);
        User::factory()->create(['status' => 'active', 'investor_id' => $investor->id]);
        $secondUser = User::factory()->create(['status' => 'active', 'investor_id' => $investor->id]);

        $this->actingAsAdminWithTenant($admin);

        $testable = Livewire::test(ListInvestors::class)
            ->callTableAction('impersonate', $investor, data: ['user_id' => $secondUser->id])
            ->assertRedirectContains('/impersonate/start/'.$secondUser->id);

        $this->get($testable->effects['redirect'])->assertRedirect('/investor');

        $this->assertSame($secondUser->id, auth()->id());
    }
}

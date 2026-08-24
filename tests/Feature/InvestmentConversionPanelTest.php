<?php

namespace Tests\Feature;

use App\Enums\InvestmentConversionDirection;
use App\Enums\InvestmentConversionSourceMode;
use App\Enums\InvestmentConversionStatus;
use App\Enums\InvestmentStatus;
use App\Filament\Resources\InvestmentConversionResource\Pages\ListInvestmentConversions;
use App\Filament\Resources\InvestmentConversionResource\Pages\ViewInvestmentConversion;
use App\Filament\Resources\InvestmentResource\Pages\ListInvestments;
use App\Models\Branch;
use App\Models\Investment;
use App\Models\InvestmentConversion;
use App\Models\InvestmentConversionSource;
use App\Models\InvestmentRateSetting;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\User;
use App\Notifications\InvestmentConversionStatusUpdated;
use App\Service\InvestmentConversionService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvestmentConversionPanelTest extends TestCase
{
    use DatabaseTransactions;

    private User $staff;

    /**
     * Admin-panel resources are tenanted by Branch — table rows cannot render
     * (ViewAction/EditAction URLs need a {tenant}) without one set. Same setup
     * ImpersonationTest uses; super_admin bypasses the resource policies.
     */
    private function actingAsAdminWithTenant(string $roleName = 'super_admin'): User
    {
        // assignRole() needs a role on the model's default guard (web, per
        // config/auth.php) — not the 'sanctum'-guarded role PermissionSeeder creates.
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        // filament-shield is configured with define_via_gate => false, so super_admin
        // carries no implicit bypass — it holds whatever permissions shield:generate
        // granted it. Those aren't seeded here, so grant just what these pages check.
        foreach ([
            'view_any_investment',
            'view_investment',
            'update_investment',
            'view_any_investment::conversion',
            'view_investment::conversion',
            'update_investment::conversion',
        ] as $permission) {
            $role->givePermissionTo(
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web'])
            );
        }

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole($role);

        $branch = Branch::create([
            'name' => 'Conversion Test Branch',
            'slug' => 'conversion-test-'.$admin->id,
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

    private Investor $investor;

    private Investment $investment;

    private User $investorUser;

    protected function setUp(): void
    {
        parent::setUp();

        InvestmentRateSetting::updateOrCreate(['year' => now()->year], ['annual_rate' => 10]);

        $this->staff = $this->actingAsAdminWithTenant();
        $this->investor = Investor::create([
            'first_name' => 'Fiifi',
            'other_names' => 'Mensah',
            'status' => 'active',
        ]);

        $this->investment = Investment::create([
            'investor_id' => $this->investor->id,
            'principal_amount' => 10000,
            'current_balance' => 12000,
            'capital_type' => 'investment',
            'start_date' => now()->subYears(2)->toDateString(),
            'contract_term_months' => 12,
            'status' => InvestmentStatus::active->value,
            'last_interest_posted_year' => now()->year,
            'last_interest_posted_through' => now()->toDateString(),
        ]);

        $this->investorUser = User::factory()->create([
            'investor_id' => $this->investor->id,
            'status' => 'active',
        ]);

        InvestmentTransaction::create([
            'investment_id' => $this->investment->id,
            'investor_id' => $this->investor->id,
            'date' => now()->toDateString(),
            'period_start' => now()->startOfYear()->toDateString(),
            'period_end' => now()->toDateString(),
            'rate_applied' => 10,
            'type' => 'interest_credit',
            'op_balance' => 10000,
            'credit' => 2000,
            'year' => now()->year,
            'posted' => true,
            'posted_at' => now(),
        ]);
    }

    public function test_staff_can_convert_an_investment_to_a_loan_from_the_investments_table(): void
    {
        Livewire::actingAs($this->staff)
            ->test(ListInvestments::class)
            ->callTableAction('convertCapital', $this->investment, [
                'source_ids' => [$this->investment->id],
                'mode' => InvestmentConversionSourceMode::full->value,
                'conversion_date' => now()->toDateString(),
                'target_contract_term_months' => 24,
                'target_payout_frequency' => 'quarterly',
                'target_annual_rate' => 9,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(InvestmentStatus::converted, $this->investment->fresh()->status);

        $conversion = InvestmentConversion::where('investor_id', $this->investor->id)->firstOrFail();
        $this->assertSame(InvestmentConversionStatus::executed, $conversion->status);

        $loan = $conversion->targetInvestment;
        $this->assertEqualsWithDelta(12000.00, (float) $loan->principal_amount, 0.01);
        $this->assertSame('loan', $loan->capital_type->value);
        $this->assertEqualsWithDelta(9.0, (float) $loan->rateOverrides()->first()->annual_rate, 0.01);
    }

    public function test_converting_to_a_loan_requires_the_loan_terms(): void
    {
        Livewire::actingAs($this->staff)
            ->test(ListInvestments::class)
            ->callTableAction('convertCapital', $this->investment, [
                'source_ids' => [$this->investment->id],
                'mode' => InvestmentConversionSourceMode::full->value,
                'conversion_date' => now()->toDateString(),
                'target_contract_term_months' => 24,
            ])
            ->assertHasTableActionErrors(['target_payout_frequency', 'target_annual_rate']);

        $this->assertSame(InvestmentStatus::active, $this->investment->fresh()->status);
    }

    public function test_staff_can_approve_and_execute_an_investor_raised_request(): void
    {
        $conversion = InvestmentConversion::create([
            'investor_id' => $this->investor->id,
            'direction' => InvestmentConversionDirection::to_loan->value,
            'conversion_date' => now()->toDateString(),
            'status' => InvestmentConversionStatus::pending_approval->value,
            'requested_by_investor' => true,
            'target_contract_term_months' => 24,
        ]);

        InvestmentConversionSource::create([
            'investment_conversion_id' => $conversion->id,
            'source_investment_id' => $this->investment->id,
            'mode' => InvestmentConversionSourceMode::full->value,
        ]);

        Livewire::actingAs($this->staff)
            ->test(ListInvestmentConversions::class)
            ->callTableAction('approve', $conversion, [
                'target_annual_rate' => 9,
                'target_payout_frequency' => 'quarterly',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(InvestmentConversionStatus::approved, $conversion->fresh()->status);

        Livewire::actingAs($this->staff)
            ->test(ListInvestmentConversions::class)
            ->callTableAction('execute', $conversion->fresh());

        $conversion->refresh();
        $this->assertSame(InvestmentConversionStatus::executed, $conversion->status);
        $this->assertNotNull($conversion->target_investment_id);
        $this->assertSame(InvestmentStatus::converted, $this->investment->fresh()->status);
    }

    public function test_rejecting_a_request_leaves_the_source_untouched(): void
    {
        $conversion = InvestmentConversion::create([
            'investor_id' => $this->investor->id,
            'direction' => InvestmentConversionDirection::to_loan->value,
            'conversion_date' => now()->toDateString(),
            'status' => InvestmentConversionStatus::pending_approval->value,
            'requested_by_investor' => true,
            'target_contract_term_months' => 24,
        ]);

        InvestmentConversionSource::create([
            'investment_conversion_id' => $conversion->id,
            'source_investment_id' => $this->investment->id,
            'mode' => InvestmentConversionSourceMode::full->value,
        ]);

        Livewire::actingAs($this->staff)
            ->test(ListInvestmentConversions::class)
            ->callTableAction('reject', $conversion, ['rejection_reason' => 'Board has not signed off.'])
            ->assertHasNoTableActionErrors();

        $conversion->refresh();
        $this->assertSame(InvestmentConversionStatus::rejected, $conversion->status);
        $this->assertSame('Board has not signed off.', $conversion->rejection_reason);

        $this->assertSame(InvestmentStatus::active, $this->investment->fresh()->status);
        $this->assertEqualsWithDelta(12000.00, (float) $this->investment->fresh()->current_balance, 0.01);
    }

    public function test_a_pending_request_cannot_be_executed_before_it_is_approved(): void
    {
        $conversion = InvestmentConversion::create([
            'investor_id' => $this->investor->id,
            'direction' => InvestmentConversionDirection::to_loan->value,
            'conversion_date' => now()->toDateString(),
            'status' => InvestmentConversionStatus::pending_approval->value,
            'target_contract_term_months' => 24,
        ]);

        Livewire::actingAs($this->staff)
            ->test(ListInvestmentConversions::class)
            ->assertTableActionHidden('execute', $conversion);
    }

    public function test_approving_a_request_notifies_the_investor(): void
    {
        $conversion = $this->pendingConversion();

        Livewire::actingAs($this->staff)
            ->test(ListInvestmentConversions::class)
            ->callTableAction('approve', $conversion, [
                'target_annual_rate' => 9,
                'target_payout_frequency' => 'quarterly',
            ])
            ->assertHasNoTableActionErrors();

        $notification = $this->investorNotifications()->first();

        $this->assertNotNull($notification, 'The investor was not told their request was approved.');
        $this->assertSame('approved', $notification->data['status']);
        $this->assertStringContainsString($conversion->fresh()->reference, $notification->data['body']);
    }

    public function test_rejecting_a_request_notifies_the_investor_with_the_reason(): void
    {
        $conversion = $this->pendingConversion();

        Livewire::actingAs($this->staff)
            ->test(ListInvestmentConversions::class)
            ->callTableAction('reject', $conversion, ['rejection_reason' => 'Board has not signed off.'])
            ->assertHasNoTableActionErrors();

        $notification = $this->investorNotifications()->first();

        $this->assertNotNull($notification, 'The investor was not told their request was rejected.');
        $this->assertSame('rejected', $notification->data['status']);
        $this->assertStringContainsString('Board has not signed off.', $notification->data['body']);
    }

    public function test_a_super_admin_can_reverse_an_executed_conversion_from_the_table(): void
    {
        $conversion = $this->executedConversion();
        $target = $conversion->targetInvestment;

        Livewire::actingAs($this->staff)
            ->test(ListInvestmentConversions::class)
            ->callTableAction('reverse', $conversion, ['reason' => 'Recorded against the wrong tranche.'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(InvestmentConversionStatus::cancelled, $conversion->fresh()->status);

        $source = $this->investment->fresh();
        $this->assertSame(InvestmentStatus::active, $source->status);
        $this->assertEqualsWithDelta(12000.00, (float) $source->current_balance, 0.01);

        $this->assertSoftDeleted('investments', ['id' => $target->id]);

        $notification = $this->investorNotifications()->first();
        $this->assertNotNull($notification, 'The investor was not told the conversion was reversed.');
        $this->assertSame('cancelled', $notification->data['status']);
        $this->assertStringContainsString('Recorded against the wrong tranche.', $notification->data['body']);
    }

    public function test_reversing_is_hidden_from_staff_without_the_super_admin_role(): void
    {
        $conversion = $this->executedConversion();
        $officer = $this->actingAsAdminWithTenant('investor_officer');

        Livewire::actingAs($officer)
            ->test(ListInvestmentConversions::class)
            ->assertTableActionHidden('reverse', $conversion)
            ->assertTableActionVisible('approve', $this->pendingConversion());
    }

    public function test_staff_can_decide_a_conversion_from_its_view_page(): void
    {
        $conversion = $this->pendingConversion();

        Livewire::actingAs($this->staff)
            ->test(ViewInvestmentConversion::class, ['record' => $conversion->id])
            ->assertActionVisible('approve')
            ->assertActionVisible('reject')
            ->assertActionHidden('execute')
            ->assertActionHidden('reverse')
            ->callAction('approve', [
                'target_annual_rate' => 9,
                'target_payout_frequency' => 'quarterly',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(InvestmentConversionStatus::approved, $conversion->fresh()->status);
    }

    public function test_an_executed_conversion_can_be_reversed_from_its_view_page(): void
    {
        $conversion = $this->executedConversion();

        Livewire::actingAs($this->staff)
            ->test(ViewInvestmentConversion::class, ['record' => $conversion->id])
            ->assertActionVisible('reverse')
            ->callAction('reverse', ['reason' => 'Wrong conversion date.'])
            ->assertHasNoActionErrors();

        $this->assertSame(InvestmentConversionStatus::cancelled, $conversion->fresh()->status);
        $this->assertSame(InvestmentStatus::active, $this->investment->fresh()->status);
    }

    private function pendingConversion(): InvestmentConversion
    {
        $conversion = InvestmentConversion::create([
            'investor_id' => $this->investor->id,
            'direction' => InvestmentConversionDirection::to_loan->value,
            'conversion_date' => now()->toDateString(),
            'status' => InvestmentConversionStatus::pending_approval->value,
            'requested_by_investor' => true,
            'target_contract_term_months' => 24,
        ]);

        InvestmentConversionSource::create([
            'investment_conversion_id' => $conversion->id,
            'source_investment_id' => $this->investment->id,
            'mode' => InvestmentConversionSourceMode::full->value,
        ]);

        return $conversion;
    }

    /**
     * An approved conversion put through the service, with the execution notification
     * cleared so a test can assert on whatever the decision under test sends next.
     */
    private function executedConversion(): InvestmentConversion
    {
        $conversion = InvestmentConversion::create([
            'investor_id' => $this->investor->id,
            'direction' => InvestmentConversionDirection::to_loan->value,
            'conversion_date' => now()->toDateString(),
            'status' => InvestmentConversionStatus::approved->value,
            'target_contract_term_months' => 24,
            'target_payout_frequency' => 'quarterly',
            'target_annual_rate' => 9,
        ]);

        InvestmentConversionSource::create([
            'investment_conversion_id' => $conversion->id,
            'source_investment_id' => $this->investment->id,
            'mode' => InvestmentConversionSourceMode::full->value,
        ]);

        app(InvestmentConversionService::class)->execute($conversion, $this->staff);

        $this->investorUser->notifications()->delete();

        return $conversion->fresh();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Notifications\DatabaseNotification>
     */
    private function investorNotifications(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->investorUser->notifications()
            ->where('type', InvestmentConversionStatusUpdated::class)
            ->get();
    }
}

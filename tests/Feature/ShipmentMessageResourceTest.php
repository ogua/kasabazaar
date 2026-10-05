<?php

namespace Tests\Feature;

use App\Enums\EmploymentStatus;
use App\Filament\Resources\ShipmentMessageResource\Pages\CreateShipmentMessage;
use App\Filament\Resources\ShipmentMessageResource\Pages\ListShipmentMessages;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Investor;
use App\Models\ShipmentMessage;
use App\Models\Staff;
use App\Models\User;
use App\Service\ShipmentMessageService;
use App\Service\SystemSetting;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

class ShipmentMessageResourceTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        SystemSetting::set('arkesel_key', 'test-key', 'sms');
        Http::preventStrayRequests();
        Http::fake(['sms.oguaschoolz.com/*' => Http::response(['status' => 'success'])]);

        $this->branch = $this->makeBranch('Message Test Branch');
    }

    private function makeBranch(string $name): Branch
    {
        $suffix = Str::random(8);

        return Branch::create([
            'name' => $name,
            'slug' => 'message-test-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '123 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);
    }

    private function makeStaff(Branch $branch, string $name, string $phone, EmploymentStatus $status = EmploymentStatus::Active): Staff
    {
        return Staff::create([
            'branch_id' => $branch->id,
            'name' => $name,
            'email' => Str::slug($name).'-'.Str::random(6).'@example.com',
            'phone' => $phone,
            'position' => 'Officer',
            'employment_status' => $status,
        ]);
    }

    private function makeInvestor(string $firstName, string $phone, string $status = 'active'): Investor
    {
        return Investor::create([
            'first_name' => $firstName,
            'email' => strtolower($firstName).'-'.Str::random(6).'@example.com',
            'phone' => $phone,
            'status' => $status,
        ]);
    }

    private function makeMessage(array $attributes): ShipmentMessage
    {
        return ShipmentMessage::create(array_merge([
            'branch_id' => $this->branch->id,
            'subject' => 'Hello {{recipient_name}}',
            'body' => '<p>Dear {{recipient_name}}, update from {{company_name}}.</p>',
            'channel' => 'both',
            'status' => 'pending',
        ], $attributes));
    }

    /**
     * @return array<int, string>
     */
    private function sentEmailRecipients(): array
    {
        return collect(Mail::mailer('array')->getSymfonyTransport()->messages())
            ->flatMap(fn (SentMessage $message) => collect($message->getEnvelope()->getRecipients())->map->getAddress())
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function sentSmsRecipients(): array
    {
        return collect(Http::recorded())
            ->map(fn (array $pair) => $pair[0]['recipients'][0] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function actingAsMessagingAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        foreach (['view_any_shipment::message', 'view_shipment::message', 'create_shipment::message'] as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole($role);
        $admin->branches()->attach($this->branch);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($this->branch);

        return $admin;
    }

    // ── Recipient resolution & delivery ─────────────────────────────────────

    public function test_all_investors_reaches_only_active_investors(): void
    {
        $kofi = $this->makeInvestor('Kofi', '0241111111');
        $esi = $this->makeInvestor('Esi', '0242222222');
        $closed = $this->makeInvestor('Yaw', '0243333333', 'inactive');

        $message = $this->makeMessage(['target_type' => 'all_investors']);
        ShipmentMessageService::processMessage($message);

        $this->assertSame('sent', $message->fresh()->status);

        $emails = $this->sentEmailRecipients();
        $this->assertContains($kofi->email, $emails);
        $this->assertContains($esi->email, $emails);
        $this->assertNotContains($closed->email, $emails);

        $sms = $this->sentSmsRecipients();
        $this->assertContains('233241111111', $sms);
        $this->assertContains('233242222222', $sms);
        $this->assertNotContains('233243333333', $sms);
    }

    public function test_a_specific_investor_gets_their_name_in_the_message(): void
    {
        $investor = $this->makeInvestor('Abena', '0244444444');
        $this->makeInvestor('Someone', '0245555555');

        $message = $this->makeMessage(['target_type' => 'investor', 'investor_id' => $investor->id, 'channel' => 'sms']);
        ShipmentMessageService::processMessage($message);

        $this->assertSame('sent', $message->fresh()->status);
        $this->assertSame([], $this->sentEmailRecipients());
        $this->assertSame(['233244444444'], $this->sentSmsRecipients());

        Http::assertSent(fn (Request $request) => str_contains($request['message'], 'Dear Abena,')
            && str_contains($request['message'], 'KASAROSE LOGISTICS')
            && ! str_contains($request['message'], '<p>'));
    }

    public function test_all_staff_reaches_only_active_staff_in_the_message_branch(): void
    {
        $ama = $this->makeStaff($this->branch, 'Ama Owusu', '0246666666');
        $onLeave = $this->makeStaff($this->branch, 'Kwame Leave', '0247777777', EmploymentStatus::OnLeave);
        $otherBranch = $this->makeStaff($this->makeBranch('Other Branch'), 'Other Branch Staff', '0248888888');

        $message = $this->makeMessage(['target_type' => 'all_staff', 'channel' => 'email']);
        ShipmentMessageService::processMessage($message);

        $this->assertSame('sent', $message->fresh()->status);

        $emails = $this->sentEmailRecipients();
        $this->assertContains($ama->email, $emails);
        $this->assertNotContains($onLeave->email, $emails);
        $this->assertNotContains($otherBranch->email, $emails);
        $this->assertSame([], $this->sentSmsRecipients());
    }

    public function test_a_specific_staff_member_is_messaged(): void
    {
        $staff = $this->makeStaff($this->branch, 'Efua Driver', '0249999999');

        $message = $this->makeMessage(['target_type' => 'staff', 'staff_id' => $staff->id]);
        ShipmentMessageService::processMessage($message);

        $this->assertSame('sent', $message->fresh()->status);
        $this->assertSame([$staff->email], $this->sentEmailRecipients());
        $this->assertSame(['233249999999'], $this->sentSmsRecipients());
    }

    public function test_client_targets_still_work_and_client_name_placeholder_is_kept(): void
    {
        $client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Ama Mensah',
            'email' => 'ama-'.Str::random(6).'@example.com',
            'phone' => '0201234567',
        ]);

        $message = $this->makeMessage([
            'target_type' => 'client',
            'client_id' => $client->id,
            'channel' => 'sms',
            'body' => 'Hi {{client_name}}',
        ]);
        ShipmentMessageService::processMessage($message);

        $this->assertSame('sent', $message->fresh()->status);
        Http::assertSent(fn (Request $request) => $request['message'] === 'Hi Ama Mensah'
            && $request['recipients'] === ['233201234567']);
    }

    public function test_a_target_with_no_recipients_is_marked_failed(): void
    {
        Staff::query()->where('branch_id', $this->branch->id)->delete();

        $message = $this->makeMessage(['target_type' => 'all_staff']);
        ShipmentMessageService::processMessage($message);

        $message->refresh();
        $this->assertSame('failed', $message->status);
        $this->assertSame('No recipients found', $message->error_message);
        Http::assertNothingSent();
    }

    // ── Filament resource ───────────────────────────────────────────────────

    public function test_the_create_page_sends_to_a_selected_investor(): void
    {
        $admin = $this->actingAsMessagingAdmin();
        $investor = $this->makeInvestor('Kojo', '0501234567');

        Livewire::test(CreateShipmentMessage::class)
            ->fillForm([
                'target_type' => 'investor',
                'investor_id' => $investor->id,
                'subject' => 'Dividend notice',
                'body' => '<p>Hello {{recipient_name}}</p>',
                'channel' => 'sms',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $message = ShipmentMessage::query()->where('investor_id', $investor->id)->sole();
        $this->assertSame('investor', $message->target_type);
        $this->assertSame('sent', $message->status);
        $this->assertSame($admin->id, $message->sent_by);
        $this->assertSame($this->branch->id, $message->branch_id);
        $this->assertSame(['233501234567'], $this->sentSmsRecipients());
    }

    public function test_the_create_page_sends_to_all_staff(): void
    {
        $this->actingAsMessagingAdmin();
        $this->makeStaff($this->branch, 'Yaa Clerk', '0551234567');

        Livewire::test(CreateShipmentMessage::class)
            ->fillForm([
                'target_type' => 'all_staff',
                'subject' => 'Staff meeting',
                'body' => '<p>Meeting at 9am, {{recipient_name}}</p>',
                'channel' => 'sms',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('sent', ShipmentMessage::query()->where('subject', 'Staff meeting')->sole()->status);
        Http::assertSent(fn (Request $request) => $request['message'] === 'Meeting at 9am, Yaa Clerk');
    }

    public function test_the_create_page_requires_the_selected_person_for_specific_targets(): void
    {
        $this->actingAsMessagingAdmin();

        foreach (['investor' => 'investor_id', 'staff' => 'staff_id', 'client' => 'client_id'] as $targetType => $field) {
            Livewire::test(CreateShipmentMessage::class)
                ->fillForm([
                    'target_type' => $targetType,
                    'subject' => 'Missing recipient',
                    'body' => '<p>Body</p>',
                    'channel' => 'email',
                ])
                ->call('create')
                ->assertHasFormErrors([$field => 'required']);
        }

        $this->assertSame(0, ShipmentMessage::query()->where('subject', 'Missing recipient')->count());
    }

    public function test_the_list_page_shows_and_filters_investor_and_staff_messages(): void
    {
        $this->actingAsMessagingAdmin();

        $toInvestors = $this->makeMessage(['target_type' => 'all_investors', 'status' => 'sent']);
        $toStaff = $this->makeMessage(['target_type' => 'all_staff', 'status' => 'sent']);

        Livewire::test(ListShipmentMessages::class)
            ->assertCanSeeTableRecords([$toInvestors, $toStaff])
            ->assertSee('All Active Investors')
            ->filterTable('target_type', 'all_staff')
            ->assertCanSeeTableRecords([$toStaff])
            ->assertCanNotSeeTableRecords([$toInvestors]);
    }
}

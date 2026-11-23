<?php

namespace Tests\Feature;

use App\Filament\Resources\ManualReceiptResource\Pages\CreateManualReceipt;
use App\Filament\Resources\ManualReceiptResource\Pages\ListManualReceipts;
use App\Filament\Resources\ManualReceiptResource\Pages\ViewManualReceipt;
use App\Models\Branch;
use App\Models\CashbookEntry;
use App\Models\Client;
use App\Models\Income;
use App\Models\ManualReceipt;
use App\Models\Payment;
use App\Models\User;
use App\Service\SystemSetting;
use App\Services\ManualReceiptService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class ManualReceiptTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $suffix = uniqid();

        $this->branch = Branch::create([
            'name' => 'Receipt Branch',
            'slug' => 'receipt-'.$suffix,
            'country' => 'Ghana',
            'state' => 'Ashanti',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);

        $this->client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Ama Mensah',
            'phone' => '0244000000',
            'email' => 'ama-'.$suffix.'@example.com',
            'address' => 'Ejisu, Kumasi',
        ]);
    }

    private function makeReceipt(array $attributes = []): ManualReceipt
    {
        return ManualReceipt::factory()->create([
            'branch_id' => $this->branch->id,
            'client_id' => $this->client->id,
            ...$attributes,
        ]);
    }

    private function actingAsStaff(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        foreach (['view_any', 'view', 'create', 'update', 'delete', 'restore'] as $ability) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $ability.'_manual::receipt', 'guard_name' => 'web']));
        }

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        $user->branches()->attach($this->branch);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($this->branch);

        return $user;
    }

    public function test_receipt_numbers_are_sequential_per_year_and_never_reused(): void
    {
        $year = now()->format('Y');
        $first = $this->makeReceipt();
        $second = $this->makeReceipt();

        $this->assertMatchesRegularExpression('/^MR-'.$year.'-\d{5}$/', $first->receipt_number);
        $this->assertSame((int) substr($first->receipt_number, -5) + 1, (int) substr($second->receipt_number, -5));

        $second->delete();

        $this->assertNotSame($second->receipt_number, $this->makeReceipt()->receipt_number);
    }

    public function test_amount_is_written_in_words_for_each_currency(): void
    {
        $this->assertSame('One thousand two hundred fifty US dollars and fifty cents only', $this->makeReceipt(['amount' => 1250.50])->amountInWords());
        $this->assertSame('Three hundred Ghana cedis only', $this->makeReceipt(['amount' => 300, 'currency' => 'GHS'])->amountInWords());
    }

    public function test_staff_can_generate_a_receipt_without_touching_the_accounts(): void
    {
        $staff = $this->actingAsStaff();

        $counts = [Payment::count(), Income::count(), CashbookEntry::count()];

        Livewire::test(CreateManualReceipt::class)
            ->fillForm([
                'client_id' => $this->client->id,
                'currency' => 'USD',
                'amount' => 850,
                'paid_on' => '2019-06-14',
                'payment_method' => 'Mobile Money',
                'description' => 'Vehicle shipping',
                'related_reference' => 'INV-2019-044',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $receipt = ManualReceipt::where('client_id', $this->client->id)->sole();

        $this->assertSame('850.00', $receipt->amount);
        $this->assertSame('2019-06-14', $receipt->paid_on->toDateString());
        $this->assertSame($this->branch->id, $receipt->branch_id);
        $this->assertSame($staff->id, $receipt->issued_by);
        $this->assertSame($counts, [Payment::count(), Income::count(), CashbookEntry::count()]);
    }

    public function test_generating_a_receipt_validates_the_essentials(): void
    {
        $this->actingAsStaff();

        Livewire::test(CreateManualReceipt::class)
            ->fillForm([
                'client_id' => null,
                'amount' => 0,
                'paid_on' => now()->addDay()->toDateString(),
                'payment_method' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['client_id' => 'required', 'amount', 'paid_on', 'payment_method' => 'required']);

        $this->assertSame(0, ManualReceipt::count());
    }

    public function test_list_and_view_pages_render(): void
    {
        $this->actingAsStaff();
        $receipt = $this->makeReceipt();

        Livewire::test(ListManualReceipts::class)
            ->assertCanSeeTableRecords([$receipt]);

        Livewire::test(ViewManualReceipt::class, ['record' => $receipt->getRouteKey()])
            ->assertSee($receipt->receipt_number)
            ->assertSee('Ama Mensah');
    }

    public function test_receipt_links_require_a_signature(): void
    {
        $receipt = $this->makeReceipt(['amount' => 420]);
        $service = app(ManualReceiptService::class);

        $this->get(route('manual-receipt', $receipt))->assertForbidden();

        $this->get($service->viewUrl($receipt))
            ->assertOk()
            ->assertSee($receipt->receipt_number)
            ->assertSee('Ama Mensah')
            ->assertSee('$420.00');

        $this->get($service->downloadUrl($receipt))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(URL::temporarySignedRoute('manual-receipt', now()->subMinute(), ['receipt' => $receipt->id]))
            ->assertForbidden();
    }

    public function test_sending_emails_the_pdf_and_texts_the_link(): void
    {
        config(['mail.default' => 'array']);
        SystemSetting::set('arkesel_key', 'arkesel-key', 'sms');
        Http::fake(['sms.oguaschoolz.com/*' => Http::response(['status' => 'success'])]);

        $receipt = $this->makeReceipt();

        $sent = app(ManualReceiptService::class)->send($receipt, 'ama@example.com', '0244000000');

        $this->assertSame(['email', 'sms'], $sent);

        /** @var Email $email */
        $email = app('mailer')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
        $this->assertSame('ama@example.com', $email->getTo()[0]->getAddress());
        $this->assertStringContainsString($receipt->receipt_number, $email->getSubject());
        $this->assertSame('receipt-'.$receipt->receipt_number.'.pdf', $email->getAttachments()[0]->getFilename());

        Http::assertSent(fn ($request) => str_contains(json_encode($request->data()), $receipt->receipt_number));

        $receipt->refresh();
        $this->assertSame(1, $receipt->times_sent);
        $this->assertNotNull($receipt->last_sent_at);
        $this->assertSame('ama@example.com, 0244000000', $receipt->last_sent_to);
    }

    public function test_send_action_needs_an_email_or_a_phone(): void
    {
        $this->actingAsStaff();
        $receipt = $this->makeReceipt();

        Livewire::test(ViewManualReceipt::class, ['record' => $receipt->getRouteKey()])
            ->callAction('send', ['email' => null, 'phone' => null])
            ->assertHasActionErrors(['email']);

        $this->assertSame(0, $receipt->refresh()->times_sent);
    }
}

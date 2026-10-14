<?php

namespace Tests\Feature;

use App\Enums\ExpenseScope;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Shipment;
use App\Models\User;
use App\Service\ExpenseService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExpenseGeneralScopeTest extends TestCase
{
    use DatabaseTransactions;

    private ?Branch $branch = null;

    private ?User $staff = null;

    public function test_a_general_expense_carries_no_shipment_container_or_stage(): void
    {
        $expense = Expense::create($this->attributes([
            'expense_for' => ExpenseScope::General,
            'shipment_id' => $this->shipment('31')->id,
            'container_number' => '31',
            'expense_stage' => 'during_shipment',
            'title' => 'Office rent',
        ]));

        $this->assertSame(ExpenseScope::General, $expense->expense_for);
        $this->assertNull($expense->shipment_id);
        $this->assertNull($expense->container_number);
        $this->assertNull($expense->fresh()->expense_stage);
        $this->assertNull($expense->subject_reference);
    }

    public function test_an_expense_with_no_subject_is_general_by_default(): void
    {
        $expense = Expense::create($this->attributes(['title' => 'Electricity bill']));

        $this->assertSame(ExpenseScope::General, $expense->expense_for);
        $this->assertNull($expense->expense_stage);
    }

    public function test_a_shipment_or_container_expense_missing_its_subject_becomes_general(): void
    {
        $noShipment = Expense::create($this->attributes(['expense_for' => ExpenseScope::Shipment]));
        $noContainer = Expense::create($this->attributes(['expense_for' => ExpenseScope::Container]));

        $this->assertSame(ExpenseScope::General, $noShipment->expense_for);
        $this->assertSame(ExpenseScope::General, $noContainer->expense_for);
    }

    public function test_reclassifying_a_shipment_expense_as_general_clears_the_shipment_and_stage(): void
    {
        $expense = Expense::create($this->attributes([
            'shipment_id' => $this->shipment('32')->id,
        ]));
        $this->assertSame(ExpenseScope::Shipment, $expense->expense_for);

        $expense->update(['expense_for' => ExpenseScope::General]);
        $expense->refresh();

        $this->assertSame(ExpenseScope::General, $expense->expense_for);
        $this->assertNull($expense->shipment_id);
        $this->assertNull($expense->expense_stage);
    }

    public function test_general_expenses_stay_out_of_shipment_and_container_totals(): void
    {
        $shipment = $this->shipment('33');

        Expense::create($this->attributes([
            'shipment_id' => $shipment->id,
            'amount_usd' => 100,
        ]));
        $general = Expense::create($this->attributes([
            'expense_for' => ExpenseScope::General,
            'amount_usd' => 900,
        ]));

        $this->assertEqualsWithDelta(100.0, (float) $shipment->expenses()->sum('amount_usd'), 0.01);
        $this->assertNotContains($general->id, Expense::forContainer('33')->pluck('id'));
        $this->assertContains($general->id, Expense::general()->pluck('id'));
    }

    public function test_service_creates_a_general_expense_with_conversion_and_branch(): void
    {
        $expense = app(ExpenseService::class)->createGeneralExpense([
            'expense_category_id' => $this->category()->id,
            'recorded_by' => $this->staff()->id,
            'title' => 'Internet subscription',
            'amount_ghs' => 600,
            'exchange_rate' => 12,
            'expense_date' => now()->toDateString(),
            'expense_stage' => 'pre_shipment',
        ], $this->branch()->id);

        $this->assertSame(ExpenseScope::General, $expense->expense_for);
        $this->assertSame($this->branch()->id, $expense->branch_id);
        $this->assertNull($expense->expense_stage);
        $this->assertEqualsWithDelta(50.0, (float) $expense->amount_usd, 0.01);
        $this->assertEqualsWithDelta(600.0, (float) $expense->amount_ghs, 0.01);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function attributes(array $overrides = []): array
    {
        return array_merge([
            'expense_category_id' => $this->category()->id,
            'branch_id' => $this->branch()->id,
            'recorded_by' => $this->staff()->id,
            'title' => 'Test expense',
            'amount_usd' => 50,
            'exchange_rate' => 10,
            'expense_date' => now()->toDateString(),
            'expense_stage' => 'during_shipment',
        ], $overrides);
    }

    private function category(): ExpenseCategory
    {
        return ExpenseCategory::firstOrCreate(
            ['code' => 'TEST-GENERAL'],
            ['name' => 'General Test Category', 'is_active' => true],
        );
    }

    private function staff(): User
    {
        return $this->staff ??= User::factory()->create();
    }

    private function branch(): Branch
    {
        return $this->branch ??= Branch::create([
            'name' => 'General Expense Test Branch',
            'slug' => 'general-expense-test-'.uniqid(),
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Road',
            'email' => 'general-expense-'.uniqid().'@example.com',
            'phone' => '0200000000',
        ]);
    }

    private function shipment(string $containerNumber): Shipment
    {
        $branch = $this->branch();

        $client = Client::create([
            'branch_id' => $branch->id,
            'name' => 'General Expense Client',
            'email' => 'general-client-'.uniqid().'@example.com',
        ]);

        return Shipment::create([
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'origin_branch_id' => $branch->id,
            'destination_branch_id' => $branch->id,
            'status' => 'pending',
            'total' => 0,
            'paid' => 0,
            'container_number' => $containerNumber,
        ]);
    }
}

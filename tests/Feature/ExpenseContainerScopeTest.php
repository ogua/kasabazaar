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

class ExpenseContainerScopeTest extends TestCase
{
    use DatabaseTransactions;

    private ?Branch $branch = null;

    private ?User $staff = null;

    public function test_a_container_expense_is_not_tied_to_a_single_shipment(): void
    {
        $expense = Expense::create($this->attributes([
            'expense_for' => ExpenseScope::Container,
            'shipment_id' => $this->shipment('77')->id,
            'container_number' => '77',
        ]));

        $this->assertSame(ExpenseScope::Container, $expense->expense_for);
        $this->assertNull($expense->shipment_id);
        $this->assertSame('77', $expense->container_number);
        $this->assertSame('CON77', $expense->subject_reference);
    }

    public function test_a_shipment_expense_never_keeps_a_container_number(): void
    {
        $shipment = $this->shipment('77');

        $expense = Expense::create($this->attributes([
            'shipment_id' => $shipment->id,
            'container_number' => '77',
        ]));

        $this->assertSame(ExpenseScope::Shipment, $expense->expense_for);
        $this->assertNull($expense->container_number);
        $this->assertSame($shipment->id, $expense->shipment_id);
    }

    public function test_entering_only_a_ghs_amount_derives_the_usd_amount(): void
    {
        $expense = Expense::create($this->attributes([
            'shipment_id' => $this->shipment('77')->id,
            'amount_usd' => null,
            'amount_ghs' => 1500,
            'exchange_rate' => 12,
        ]));

        $this->assertEqualsWithDelta(125.0, (float) $expense->amount_usd, 0.01);
        $this->assertEqualsWithDelta(1500.0, (float) $expense->amount_ghs, 0.01);
    }

    public function test_editing_the_ghs_amount_recomputes_usd_and_vice_versa(): void
    {
        $expense = Expense::create($this->attributes([
            'shipment_id' => $this->shipment('77')->id,
            'amount_usd' => 100,
            'exchange_rate' => 10,
        ]));
        $this->assertEqualsWithDelta(1000.0, (float) $expense->amount_ghs, 0.01);

        $expense->update(['amount_ghs' => 2500]);
        $this->assertEqualsWithDelta(250.0, (float) $expense->fresh()->amount_usd, 0.01);

        $expense->update(['amount_usd' => 300]);
        $this->assertEqualsWithDelta(3000.0, (float) $expense->fresh()->amount_ghs, 0.01);
    }

    public function test_for_container_scope_covers_direct_and_member_shipment_expenses(): void
    {
        $inContainer = $this->shipment('88');
        $elsewhere = $this->shipment('99');

        $memberExpense = Expense::create($this->attributes([
            'shipment_id' => $inContainer->id,
            'container_number' => '88',
            'title' => 'Member shipment cost',
        ]));

        $directExpense = Expense::create($this->attributes([
            'expense_for' => ExpenseScope::Container,
            'container_number' => '88',
            'title' => 'Whole-container demurrage',
        ]));

        $unrelated = Expense::create($this->attributes([
            'shipment_id' => $elsewhere->id,
            'title' => 'Different container cost',
        ]));

        $ids = Expense::forContainer('88')->pluck('id');

        $this->assertContains($memberExpense->id, $ids);
        $this->assertContains($directExpense->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
    }

    public function test_service_creates_a_container_expense_with_conversion_and_branch(): void
    {
        $shipment = $this->shipment('123');

        $expense = app(ExpenseService::class)->createContainerExpense('123', [
            'expense_category_id' => $this->category()->id,
            'recorded_by' => $this->staff()->id,
            'title' => 'Clearing agent fee',
            'amount_usd' => 200,
            'exchange_rate' => 12,
            'expense_date' => now()->toDateString(),
            'expense_stage' => 'post_shipment',
        ]);

        $this->assertSame(ExpenseScope::Container, $expense->expense_for);
        $this->assertSame('123', $expense->container_number);
        $this->assertNull($expense->shipment_id);
        $this->assertSame($shipment->branch_id, $expense->branch_id);
        $this->assertEqualsWithDelta(2400.0, (float) $expense->amount_ghs, 0.01);
    }

    public function test_container_expense_summary_totals_both_sources(): void
    {
        $shipment = $this->shipment('456');

        Expense::create($this->attributes([
            'shipment_id' => $shipment->id,
            'container_number' => '456',
            'amount_usd' => 100,
            'exchange_rate' => 10,
        ]));

        Expense::create($this->attributes([
            'expense_for' => ExpenseScope::Container,
            'container_number' => '456',
            'amount_usd' => 400,
            'exchange_rate' => 10,
        ]));

        $summary = app(ExpenseService::class)->getContainerExpenseSummary('456');

        $this->assertSame(2, $summary['count']);
        $this->assertEqualsWithDelta(500.0, (float) $summary['total_usd'], 0.01);
        $this->assertEqualsWithDelta(400.0, (float) $summary['direct_total_usd'], 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $summary['shipment_total_usd'], 0.01);
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
            ['code' => 'TEST-CONTAINER'],
            ['name' => 'Container Test Category', 'is_active' => true],
        );
    }

    private function staff(): User
    {
        return $this->staff ??= User::factory()->create();
    }

    private function branch(): Branch
    {
        return $this->branch ??= Branch::create([
            'name' => 'Container Expense Test Branch',
            'slug' => 'container-expense-test-'.uniqid(),
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Road',
            'email' => 'container-expense-'.uniqid().'@example.com',
            'phone' => '0200000000',
        ]);
    }

    private function shipment(string $containerNumber): Shipment
    {
        $branch = $this->branch();

        $client = Client::create([
            'branch_id' => $branch->id,
            'name' => 'Container Client',
            'email' => 'container-client-'.uniqid().'@example.com',
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

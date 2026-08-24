<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Shipment;
use App\Service\FinancialStatementService;
use Carbon\Carbon;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The dashboard KPI and the bank-facing AR schedule describe the same thing, so they
 * must agree. They did not: the widget joined shipments to payments and summed
 * shipments.total across the join, counting any shipment with more than one payment
 * twice.
 */
class ReceivablesConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);

        $this->branch = Branch::create([
            'name' => 'Receivables Test Branch',
            'slug' => 'receivables-test-'.uniqid(),
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Road',
            'email' => 'receivables-'.uniqid().'@example.com',
            'phone' => '0200000000',
        ]);
    }

    public function test_a_shipment_with_several_payments_is_not_counted_more_than_once(): void
    {
        $client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Part Payer',
            'email' => 'part-payer-'.uniqid().'@example.com',
        ]);

        // $10,000 invoiced, $3,000 settled across three separate payments. The old
        // query summed the shipment total once per payment row — $30,000 of "invoiced".
        $shipment = Shipment::create([
            'client_id' => $client->id,
            'branch_id' => $this->branch->id,
            'origin_branch_id' => $this->branch->id,
            'destination_branch_id' => $this->branch->id,
            'status' => 'pending',
            'total' => 10000,
            'paid' => 3000,
        ]);

        foreach ([1000, 1000, 1000] as $amount) {
            Payment::create([
                'shipment_id' => $shipment->id,
                'branch_id' => $this->branch->id,
                'amount' => $amount,
                'amount_usd' => $amount,
                'payment_type' => 'credit',
            ]);
        }

        $widgetFigure = (float) DB::table('shipments')
            ->whereRaw('total > paid')
            ->where('shipments.id', $shipment->id)
            ->selectRaw('COALESCE(SUM(shipments.total - shipments.paid), 0) as outstanding')
            ->value('outstanding');

        $this->assertEqualsWithDelta(7000.00, $widgetFigure, 0.01);
    }

    public function test_the_dashboard_figure_matches_the_bank_facing_schedule(): void
    {
        $client = Client::create([
            'branch_id' => $this->branch->id,
            'name' => 'Schedule Client',
            'email' => 'schedule-'.uniqid().'@example.com',
        ]);

        foreach ([[8000, 2000], [5000, 5000], [3000, 0]] as [$total, $paid]) {
            $shipment = Shipment::create([
                'client_id' => $client->id,
                'branch_id' => $this->branch->id,
                'origin_branch_id' => $this->branch->id,
                'destination_branch_id' => $this->branch->id,
                'status' => 'pending',
                'total' => $total,
                'paid' => $paid,
            ]);

            $shipment->forceFill(['created_at' => '2026-06-01'])->saveQuietly();
        }

        $schedule = app(FinancialStatementService::class)
            ->accountsReceivable(2026, Carbon::parse('2026-12-31'));

        // $6,000 still owing on the first, nothing on the settled one, $3,000 on the last.
        $this->assertEqualsWithDelta(9000.00, $schedule['totals']['outstanding'], 0.01);

        $widgetFigure = (float) DB::table('shipments')
            ->whereRaw('total > paid')
            ->where('shipments.client_id', $client->id)
            ->selectRaw('COALESCE(SUM(shipments.total - shipments.paid), 0) as outstanding')
            ->value('outstanding');

        $this->assertEqualsWithDelta(
            $schedule['totals']['outstanding'],
            $widgetFigure,
            0.01,
            'The dashboard KPI and the AR schedule must report the same outstanding balance.'
        );
    }
}

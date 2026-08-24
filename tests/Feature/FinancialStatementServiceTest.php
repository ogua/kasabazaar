<?php

namespace Tests\Feature;

use App\Enums\FiscalPeriodSource;
use App\Enums\FiscalPeriodStatus;
use App\Models\AccountBalance;
use App\Models\Branch;
use App\Models\CashbookExpenditureLedger;
use App\Models\CashbookIncomeLedger;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FiscalPeriod;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\Shipment;
use App\Models\User;
use App\Service\FinancialStatementService;
use Carbon\Carbon;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\IncomeCategorySeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinancialStatementServiceTest extends TestCase
{
    use DatabaseTransactions;

    private FinancialStatementService $service;

    private const DERIVED_YEAR = 2026;

    private const MANUAL_YEAR = 2024;

    private ?Branch $branch = null;

    private ?User $staff = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Order matters and mirrors DatabaseSeeder: the categories must exist before
        // ChartOfAccountsSeeder can point them at their accounts.
        $this->seed(ExpenseCategorySeeder::class);
        $this->seed(IncomeCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);
        $this->service = app(FinancialStatementService::class);
    }

    private function derivedPeriod(): FiscalPeriod
    {
        return FiscalPeriod::updateOrCreate(
            ['year' => self::DERIVED_YEAR],
            [
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'source' => FiscalPeriodSource::derived->value,
                'status' => FiscalPeriodStatus::open->value,
                // Pinned so the test is not at the mercy of the live rate feed.
                'closing_exchange_rate' => 10.0,
            ]
        );
    }

    public function test_the_chart_of_accounts_seeds_a_complete_set_of_statement_lines(): void
    {
        $this->assertGreaterThan(0, ChartOfAccount::ofType(\App\Enums\AccountType::asset)->count());
        $this->assertGreaterThan(0, ChartOfAccount::ofType(\App\Enums\AccountType::liability)->count());
        $this->assertGreaterThan(0, ChartOfAccount::ofType(\App\Enums\AccountType::equity)->count());

        // The cost of investor capital must exist as a line, or the P&L would show no
        // funding cost against a balance sheet full of investor liabilities.
        $this->assertNotNull(ChartOfAccount::where('code', 'EXP-INVESTOR-INTEREST')->first());
        $this->assertNotNull(ChartOfAccount::where('code', 'LIA-INVESTOR-CAPITAL')->first());
    }

    public function test_a_derived_profit_and_loss_translates_the_ghs_cashbook_into_usd(): void
    {
        // The cashbook is the alternative trading source, not the default.
        config()->set('financials.trading_source', 'cashbook');

        $this->derivedPeriod();

        // GHS 100,000 of shipping income at a 10.0 closing rate = USD 10,000.
        CashbookIncomeLedger::create([
            'month' => 6, 'year' => self::DERIVED_YEAR, 'ledger_type' => 'shipping',
            'op_balance' => 0, 'credit' => 100000, 'debit' => 0,
        ]);

        // GHS 40,000 of shipping/import cost = USD 4,000, classified as cost of sales.
        CashbookExpenditureLedger::create([
            'month' => 6, 'year' => self::DERIVED_YEAR, 'ledger_type' => 'shipping_import',
            'op_balance' => 0, 'debit' => 40000, 'credit' => 0,
        ]);

        $statement = $this->service->profitAndLoss(self::DERIVED_YEAR);

        $this->assertSame('derived', $statement['source']);
        $this->assertSame('USD', $statement['currency']);
        $this->assertEqualsWithDelta(10000.00, $statement['totals']['revenue'], 0.01);
        $this->assertEqualsWithDelta(4000.00, $statement['totals']['cost_of_sales'], 0.01);
        $this->assertEqualsWithDelta(6000.00, $statement['totals']['gross_profit'], 0.01);
        $this->assertEqualsWithDelta(60.0, $statement['totals']['gross_margin_percentage'], 0.01);
    }

    public function test_investor_interest_lands_on_the_profit_and_loss_as_a_finance_cost(): void
    {
        $this->derivedPeriod();

        $investor = Investor::create(['first_name' => 'Fiifi', 'status' => 'active']);
        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'current_balance' => 11000,
            'capital_type' => 'investment',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investor->id,
            'date' => '2026-12-31',
            'type' => 'interest_credit',
            'op_balance' => 10000,
            'credit' => 1000,
            'year' => self::DERIVED_YEAR,
            'posted' => true,
            'posted_at' => now(),
        ]);

        $statement = $this->service->profitAndLoss(self::DERIVED_YEAR);

        $this->assertEqualsWithDelta(1000.00, $statement['totals']['finance_costs'], 0.01);
        $this->assertEqualsWithDelta(-1000.00, $statement['totals']['net_profit'], 0.01);
    }

    public function test_the_balance_sheet_carries_investor_capital_as_a_liability(): void
    {
        $this->derivedPeriod();

        $investor = Investor::create(['first_name' => 'Fiifi', 'status' => 'active']);

        Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'current_balance' => 11000,
            'capital_type' => 'investment',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 5000,
            'current_balance' => 5000,
            'capital_type' => 'loan',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $sheet = $this->service->balanceSheet(self::DERIVED_YEAR);

        $investorCapital = collect($sheet['liabilities']['long_term'])
            ->firstWhere('statement_line', 'Investor Capital');

        $this->assertNotNull($investorCapital, 'Investor capital must appear on the balance sheet.');
        // 11,000 compounding balance + 5,000 loan principal.
        $this->assertEqualsWithDelta(16000.00, $investorCapital['amount'], 0.01);
    }

    /**
     * The payoff of the conversion work being correct: a settled tranche and its
     * successor both exist as rows, but only one of them is money the company owes.
     */
    public function test_a_converted_tranche_is_not_double_counted_as_a_liability(): void
    {
        $this->derivedPeriod();

        $investor = Investor::create(['first_name' => 'Fiifi', 'status' => 'active']);

        Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 10000,
            'current_balance' => 0,
            'capital_type' => 'investment',
            'start_date' => '2026-01-01',
            'status' => 'converted',
        ]);

        Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => 12000,
            'current_balance' => 12000,
            'capital_type' => 'loan',
            'start_date' => '2026-06-01',
            'status' => 'active',
        ]);

        $sheet = $this->service->balanceSheet(self::DERIVED_YEAR);

        $investorCapital = collect($sheet['liabilities']['long_term'])
            ->firstWhere('statement_line', 'Investor Capital');

        $this->assertEqualsWithDelta(
            12000.00,
            $investorCapital['amount'],
            0.01,
            'Only the successor tranche is a live liability — the converted one is settled.'
        );
    }

    public function test_a_manual_year_reads_its_keyed_in_balances_rather_than_live_data(): void
    {
        FiscalPeriod::updateOrCreate(
            ['year' => self::MANUAL_YEAR],
            [
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'source' => FiscalPeriodSource::manual->value,
                'status' => FiscalPeriodStatus::locked->value,
                'closing_exchange_rate' => 12.0,
            ]
        );

        $this->keyIn(self::MANUAL_YEAR, 'INC-SHIPPING', movement: 250000);
        $this->keyIn(self::MANUAL_YEAR, 'EXP-SHIPPING_IMPORT', movement: 100000);
        $this->keyIn(self::MANUAL_YEAR, 'EXP-SALARIES_WAGES', movement: 60000);

        $statement = $this->service->profitAndLoss(self::MANUAL_YEAR);

        $this->assertSame('manual', $statement['source']);
        $this->assertEqualsWithDelta(250000.00, $statement['totals']['revenue'], 0.01);
        $this->assertEqualsWithDelta(100000.00, $statement['totals']['cost_of_sales'], 0.01);
        $this->assertEqualsWithDelta(60000.00, $statement['totals']['operating_expenses'], 0.01);
        $this->assertEqualsWithDelta(90000.00, $statement['totals']['net_profit'], 0.01);
    }

    public function test_a_manual_balance_sheet_balances_when_the_trial_balance_articulates(): void
    {
        FiscalPeriod::updateOrCreate(
            ['year' => self::MANUAL_YEAR],
            [
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'source' => FiscalPeriodSource::manual->value,
                'closing_exchange_rate' => 12.0,
            ]
        );

        $this->keyIn(self::MANUAL_YEAR, 'AST-BANK', closing: 150000);
        $this->keyIn(self::MANUAL_YEAR, 'AST-AR', closing: 50000);
        $this->keyIn(self::MANUAL_YEAR, 'LIA-AP', closing: 40000);
        $this->keyIn(self::MANUAL_YEAR, 'EQY-CAPITAL', closing: 100000);
        $this->keyIn(self::MANUAL_YEAR, 'EQY-RETAINED', closing: 60000);

        $sheet = $this->service->balanceSheet(self::MANUAL_YEAR);

        $this->assertEqualsWithDelta(200000.00, $sheet['totals']['total_assets'], 0.01);
        $this->assertEqualsWithDelta(40000.00, $sheet['totals']['total_liabilities'], 0.01);
        $this->assertEqualsWithDelta(160000.00, $sheet['totals']['total_equity'], 0.01);
        $this->assertTrue($sheet['is_balanced'], 'Assets must equal liabilities plus equity.');
        $this->assertEqualsWithDelta(0.0, $sheet['imbalance'], 0.01);
    }

    public function test_an_out_of_balance_year_is_reported_rather_than_hidden(): void
    {
        FiscalPeriod::updateOrCreate(
            ['year' => self::MANUAL_YEAR],
            ['start_date' => '2024-01-01', 'end_date' => '2024-12-31', 'source' => FiscalPeriodSource::manual->value, 'closing_exchange_rate' => 12.0]
        );

        $this->keyIn(self::MANUAL_YEAR, 'AST-BANK', closing: 150000);
        $this->keyIn(self::MANUAL_YEAR, 'LIA-AP', closing: 40000);

        $sheet = $this->service->balanceSheet(self::MANUAL_YEAR);

        $this->assertFalse($sheet['is_balanced']);
        $this->assertEqualsWithDelta(110000.00, $sheet['imbalance'], 0.01);

        $check = $this->service->trialBalanceCheck(self::MANUAL_YEAR);
        $this->assertFalse($check['balances']);
        $this->assertEqualsWithDelta(110000.00, $check['difference'], 0.01);
    }

    public function test_receivables_are_aged_into_buckets_as_at_an_explicit_date(): void
    {
        $this->derivedPeriod();

        $client = $this->client();

        // 15 days old at the as-of date.
        $this->shipmentOwing($client, total: 1000, paid: 0, createdAt: '2026-12-16');
        // 75 days old.
        $this->shipmentOwing($client, total: 2000, paid: 500, createdAt: '2026-10-17');
        // Fully settled — must not appear.
        $this->shipmentOwing($client, total: 3000, paid: 3000, createdAt: '2026-05-01');

        $schedule = $this->service->accountsReceivable(self::DERIVED_YEAR, Carbon::parse('2026-12-31'));

        $this->assertSame('2026-12-31', $schedule['as_of']);
        $this->assertEqualsWithDelta(2500.00, $schedule['totals']['outstanding'], 0.01);
        $this->assertSame(2, $schedule['totals']['shipment_count']);

        $buckets = collect($schedule['aging'])->keyBy('bucket');
        $this->assertEqualsWithDelta(1000.00, $buckets['0-30']['amount'], 0.01);
        $this->assertEqualsWithDelta(1500.00, $buckets['61-90']['amount'], 0.01);
    }

    public function test_a_year_before_the_first_recorded_year_defaults_to_manual_entry(): void
    {
        $period = $this->service->periodFor(2023);

        $this->assertSame(FiscalPeriodSource::manual, $period->source);
        $this->assertSame('2023-01-01', $period->start_date->toDateString());
        $this->assertSame('2023-12-31', $period->end_date->toDateString());
    }

    /**
     * The statements are only useful if they actually print — DomPDF falls back
     * silently on a bad blade, so each is rendered for real rather than asserted on
     * its array payload alone.
     */
    public function test_each_statement_renders_a_real_pdf(): void
    {
        $this->derivedPeriod();

        CashbookIncomeLedger::create([
            'month' => 3, 'year' => self::DERIVED_YEAR, 'ledger_type' => 'shipping',
            'op_balance' => 0, 'credit' => 50000, 'debit' => 0,
        ]);

        $views = [
            'reports.profit-and-loss-pdf' => $this->service->profitAndLoss(self::DERIVED_YEAR),
            'reports.balance-sheet-pdf' => $this->service->balanceSheet(self::DERIVED_YEAR),
            'reports.accounts-receivable-pdf' => $this->service->accountsReceivable(self::DERIVED_YEAR),
        ];

        foreach ($views as $view => $statement) {
            $output = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, ['statement' => $statement])
                ->setPaper('a4', 'portrait')
                ->output();

            $this->assertStringStartsWith('%PDF', $output, "{$view} must render a real PDF.");
        }
    }

    /**
     * A bank that asks for the figures in Excel must get exactly what the PDF shows,
     * so the exports are driven off the same statement payload and rendered for real.
     */
    public function test_each_statement_exports_a_real_spreadsheet(): void
    {
        \Maatwebsite\Excel\Facades\Excel::fake();

        $this->derivedPeriod();

        CashbookIncomeLedger::create([
            'month' => 3, 'year' => self::DERIVED_YEAR, 'ledger_type' => 'shipping',
            'op_balance' => 0, 'credit' => 50000, 'debit' => 0,
        ]);

        $exports = [
            'profit-and-loss.xlsx' => new \App\Exports\ProfitAndLossExport($this->service->profitAndLoss(self::DERIVED_YEAR)),
            'balance-sheet.xlsx' => new \App\Exports\BalanceSheetExport($this->service->balanceSheet(self::DERIVED_YEAR)),
            'accounts-receivable.xlsx' => new \App\Exports\AccountsReceivableExport($this->service->accountsReceivable(self::DERIVED_YEAR)),
        ];

        foreach ($exports as $filename => $export) {
            \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
            \Maatwebsite\Excel\Facades\Excel::assertDownloaded($filename);
        }
    }

    /**
     * The Excel P&L must total to the same profit the PDF states — they are driven
     * off one payload, and this pins that they stay that way.
     */
    public function test_the_excel_profit_and_loss_carries_the_same_net_profit(): void
    {
        $this->derivedPeriod();

        CashbookIncomeLedger::create([
            'month' => 3, 'year' => self::DERIVED_YEAR, 'ledger_type' => 'shipping',
            'op_balance' => 0, 'credit' => 100000, 'debit' => 0,
        ]);

        $statement = $this->service->profitAndLoss(self::DERIVED_YEAR);
        $rows = (new \App\Exports\ProfitAndLossExport($statement))->collection();

        $netProfitRow = $rows->first(fn ($row) => $row[0] === 'PROFIT FOR THE YEAR');

        $this->assertNotNull($netProfitRow, 'The export must state a profit for the year.');
        $this->assertEqualsWithDelta($statement['totals']['net_profit'], $netProfitRow[1], 0.01);
    }

    /**
     * The accountant's Ghana books are in Cedis while these statements present in USD.
     * Reading the keyed figures verbatim would overstate the year by the exchange rate
     * and still label the result USD — on a document going to a lender.
     */
    public function test_a_manual_year_keyed_in_cedis_is_translated_into_the_presentation_currency(): void
    {
        FiscalPeriod::updateOrCreate(
            ['year' => self::MANUAL_YEAR],
            [
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'source' => FiscalPeriodSource::manual->value,
                'entry_currency' => 'GHS',
                'closing_exchange_rate' => 12.0,
            ]
        );

        $this->keyIn(self::MANUAL_YEAR, 'INC-SHIPPING', movement: 1200000);
        $this->keyIn(self::MANUAL_YEAR, 'EXP-SHIPPING_IMPORT', movement: 600000);

        $statement = $this->service->profitAndLoss(self::MANUAL_YEAR);

        // GHS 1,200,000 at 12.0 = USD 100,000.
        $this->assertEqualsWithDelta(100000.00, $statement['totals']['revenue'], 0.01);
        $this->assertEqualsWithDelta(50000.00, $statement['totals']['cost_of_sales'], 0.01);
        $this->assertEqualsWithDelta(50000.00, $statement['totals']['gross_profit'], 0.01);
        $this->assertSame('USD', $statement['currency']);
    }

    public function test_a_manual_year_keyed_in_usd_is_left_untranslated(): void
    {
        FiscalPeriod::updateOrCreate(
            ['year' => self::MANUAL_YEAR],
            [
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'source' => FiscalPeriodSource::manual->value,
                'entry_currency' => 'USD',
                'closing_exchange_rate' => 12.0,
            ]
        );

        $this->keyIn(self::MANUAL_YEAR, 'INC-SHIPPING', movement: 100000);

        $statement = $this->service->profitAndLoss(self::MANUAL_YEAR);

        $this->assertEqualsWithDelta(100000.00, $statement['totals']['revenue'], 0.01);
    }

    public function test_a_cedi_balance_sheet_still_balances_after_translation(): void
    {
        FiscalPeriod::updateOrCreate(
            ['year' => self::MANUAL_YEAR],
            [
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'source' => FiscalPeriodSource::manual->value,
                'entry_currency' => 'GHS',
                'closing_exchange_rate' => 12.0,
            ]
        );

        $this->keyIn(self::MANUAL_YEAR, 'AST-BANK', closing: 1200000);
        $this->keyIn(self::MANUAL_YEAR, 'LIA-AP', closing: 240000);
        $this->keyIn(self::MANUAL_YEAR, 'EQY-CAPITAL', closing: 960000);

        $sheet = $this->service->balanceSheet(self::MANUAL_YEAR);

        $this->assertEqualsWithDelta(100000.00, $sheet['totals']['total_assets'], 0.01);
        $this->assertEqualsWithDelta(20000.00, $sheet['totals']['total_liabilities'], 0.01);
        $this->assertEqualsWithDelta(80000.00, $sheet['totals']['total_equity'], 0.01);
        $this->assertTrue($sheet['is_balanced'], 'Translation must not break the balance.');
    }

    /**
     * The default source. The business runs on shipments and expense records, not the
     * cashbook, so this is the path that produces its real statements.
     */
    public function test_a_derived_profit_and_loss_reads_shipments_and_expense_records(): void
    {
        $this->derivedPeriod();
        $client = $this->client();

        $this->shipmentOwing($client, total: 12000, paid: 5000, createdAt: '2026-03-04');
        $this->shipmentOwing($client, total: 8000, paid: 8000, createdAt: '2026-04-11');

        // Customs maps to cost of sales, fuel to operating expenses.
        $this->expense('CUSTOMS', 3000, '2026-03-10');
        $this->expense('FUEL', 1000, '2026-04-02');

        $statement = $this->service->profitAndLoss(self::DERIVED_YEAR);

        // Revenue is recognised when the shipment is raised, not when it is paid.
        $this->assertEqualsWithDelta(20000.00, $statement['totals']['revenue'], 0.01);
        $this->assertEqualsWithDelta(3000.00, $statement['totals']['cost_of_sales'], 0.01);
        $this->assertEqualsWithDelta(17000.00, $statement['totals']['gross_profit'], 0.01);
        $this->assertEqualsWithDelta(1000.00, $statement['totals']['operating_expenses'], 0.01);
        $this->assertEqualsWithDelta(16000.00, $statement['totals']['net_profit'], 0.01);
    }

    /**
     * The two sources record the cash and accrual sides of the same transactions, so
     * only one may ever be read — otherwise every trade is counted twice.
     */
    public function test_the_cashbook_is_ignored_while_records_are_the_trading_source(): void
    {
        $this->derivedPeriod();

        CashbookIncomeLedger::create([
            'month' => 6, 'year' => self::DERIVED_YEAR, 'ledger_type' => 'shipping',
            'op_balance' => 0, 'credit' => 100000, 'debit' => 0,
        ]);

        $this->shipmentOwing($this->client(), total: 5000, paid: 0, createdAt: '2026-06-01');

        $statement = $this->service->profitAndLoss(self::DERIVED_YEAR);

        // The shipment alone — not the shipment plus GHS 100,000 of cashbook receipts.
        $this->assertEqualsWithDelta(5000.00, $statement['totals']['revenue'], 0.01);
    }

    public function test_an_unmapped_expense_category_still_reaches_the_statement(): void
    {
        $this->derivedPeriod();

        // No chart_of_account_id — must land in the catch-all rather than vanish.
        $this->expense('UNMAPPED', 750, '2026-05-05', mapped: false);

        $statement = $this->service->profitAndLoss(self::DERIVED_YEAR);

        $this->assertEqualsWithDelta(750.00, $statement['totals']['operating_expenses'], 0.01);
    }

    /**
     * With no cashbook there is no cash figure anywhere in the system, so a keyed
     * opening balance has to carry into later derived years or the balance sheet can
     * never be made to balance.
     */
    public function test_a_keyed_cash_balance_carries_into_a_derived_year(): void
    {
        $this->derivedPeriod();

        FiscalPeriod::updateOrCreate(
            ['year' => 2025],
            [
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'source' => FiscalPeriodSource::manual->value,
                'entry_currency' => 'USD',
            ]
        );

        $this->keyIn(2025, 'AST-BANK', closing: 45000);
        $this->keyIn(2025, 'EQY-CAPITAL', closing: 45000);

        $sheet = $this->service->balanceSheet(self::DERIVED_YEAR);

        $cash = collect($sheet['assets']['current'])->firstWhere('statement_line', 'Cash & Bank');
        $this->assertNotNull($cash, 'A keyed cash balance must appear on a derived year.');
        $this->assertEqualsWithDelta(45000.00, $cash['amount'], 0.01);

        $this->assertEqualsWithDelta(45000.00, collect($sheet['equity'])
            ->firstWhere('statement_line', 'Share Capital')['amount'], 0.01);
    }

    /**
     * An unmapped category still reports, but it lands in the catch-all and quietly
     * understates cost of sales. Production hit exactly this: five user-created
     * categories carried most of the spend and the gross margin came out at 99%.
     */
    public function test_unmapped_categories_are_reported_against_the_statement(): void
    {
        $this->derivedPeriod();

        $this->shipmentOwing($this->client(), total: 100000, paid: 0, createdAt: '2026-02-01');
        $this->expense('CUSTOMS', 2000, '2026-02-05');
        $this->expense('Container Fee', 40000, '2026-02-06', mapped: false);
        $this->expense('Lunch', 500, '2026-02-07', mapped: false);

        $statement = $this->service->profitAndLoss(self::DERIVED_YEAR);
        $unmapped = $statement['unmapped_categories'];

        $this->assertEqualsWithDelta(40500.00, $unmapped['total'], 0.01);

        // Largest first, so the one worth fixing is the one read first.
        $this->assertSame('Container Fee', $unmapped['expenses'][0]['name']);
        $this->assertEqualsWithDelta(40000.00, $unmapped['expenses'][0]['amount'], 0.01);

        // The money is still reported — it just sits in the wrong caption.
        $this->assertEqualsWithDelta(40500.00, $statement['totals']['operating_expenses'], 0.01);
        $this->assertEqualsWithDelta(2000.00, $statement['totals']['cost_of_sales'], 0.01);
    }

    public function test_a_fully_mapped_year_reports_nothing_unmapped(): void
    {
        $this->derivedPeriod();

        $this->shipmentOwing($this->client(), total: 5000, paid: 0, createdAt: '2026-02-01');
        $this->expense('CUSTOMS', 1000, '2026-02-05');

        $statement = $this->service->profitAndLoss(self::DERIVED_YEAR);

        $this->assertEqualsWithDelta(0.0, $statement['unmapped_categories']['total'], 0.01);
        $this->assertSame([], $statement['unmapped_categories']['expenses']);
    }

    /**
     * Keying a derived year would flip it to 'manual' and its statements would stop
     * reading live records — silently replacing real trading figures with whatever was
     * typed. The entry screen must not offer those years at all.
     */
    public function test_prior_year_entry_only_offers_years_that_have_to_be_keyed(): void
    {
        $years = array_keys(\App\Filament\Pages\FinancialStatementEntry::keyableYears());
        $firstRecorded = \App\Service\FinancialStatementService::firstRecordedYear();

        $this->assertNotEmpty($years);
        $this->assertSame($firstRecorded - 1, max($years), 'The latest keyable year must precede the first derived year.');

        foreach ($years as $year) {
            $this->assertLessThan($firstRecorded, $year, "{$year} is derived and must not be keyable.");
        }
    }

    private function expense(string $categoryCode, float $amount, string $date, bool $mapped = true): Expense
    {
        $category = ExpenseCategory::firstOrCreate(
            ['code' => $categoryCode],
            ['name' => $categoryCode, 'is_active' => true]
        );

        if (! $mapped) {
            $category->update(['chart_of_account_id' => null]);
        }

        return Expense::create([
            'shipment_id' => $this->shipmentOwing($this->client(), 0, 0, $date)->id,
            'expense_category_id' => $category->id,
            'branch_id' => $this->branch()->id,
            'recorded_by' => $this->staff()->id,
            'reference' => 'EXP-'.uniqid(),
            'title' => $categoryCode.' cost',
            'amount_usd' => $amount,
            'exchange_rate' => 10,
            'amount_ghs' => $amount * 10,
            'expense_date' => $date,
            'expense_stage' => 'during_shipment',
        ]);
    }

    private function staff(): User
    {
        return $this->staff ??= User::factory()->create();
    }

    private function keyIn(int $year, string $code, float $movement = 0, ?float $closing = null): void
    {
        $account = ChartOfAccount::where('code', $code)->firstOrFail();

        AccountBalance::updateOrCreate(
            ['fiscal_year' => $year, 'chart_of_account_id' => $account->id],
            ['opening_balance' => 0, 'movement' => $closing ?? $movement]
        );
    }

    /**
     * There are no model factories for Branch/Client/Shipment in this codebase, so
     * these are built directly with just the columns the schema requires.
     */
    private function branch(): Branch
    {
        return $this->branch ??= Branch::create([
            'name' => 'Financials Test Branch',
            'slug' => 'financials-test-'.uniqid(),
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Road',
            'email' => 'financials-'.uniqid().'@example.com',
            'phone' => '0200000000',
        ]);
    }

    private function client(): Client
    {
        $branch = $this->branch();

        return Client::create([
            'branch_id' => $branch->id,
            'name' => 'Aged Client',
            'email' => 'aged-client-'.uniqid().'@example.com',
        ]);
    }

    private function shipmentOwing(Client $client, float $total, float $paid, string $createdAt): Shipment
    {
        $branch = $this->branch();

        $shipment = Shipment::create([
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'origin_branch_id' => $branch->id,
            'destination_branch_id' => $branch->id,
            'status' => 'pending',
            'total' => $total,
            'paid' => $paid,
        ]);

        // created_at drives the ageing bucket, and it is stamped as "now" on insert.
        $shipment->forceFill(['created_at' => $createdAt])->saveQuietly();

        return $shipment;
    }
}

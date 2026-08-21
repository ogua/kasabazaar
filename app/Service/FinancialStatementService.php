<?php

namespace App\Service;

use App\Enums\AccountSubtype;
use App\Enums\AccountType;
use App\Enums\FiscalPeriodSource;
use App\Enums\IncomeStatus;
use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentStatus;
use App\Enums\InvestmentTransactionType;
use App\Models\AccountBalance;
use App\Models\CashbookDirectorAccount;
use App\Models\CashbookEntry;
use App\Models\CashbookExpenditureLedger;
use App\Models\CashbookIncomeLedger;
use App\Models\CashbookLoan;
use App\Models\CashbookWithholdingTax;
use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\Income;
use App\Models\Investment;
use App\Models\InvestmentInterestPayout;
use App\Models\InvestmentTransaction;
use App\Models\Invoice;
use App\Models\PayrollEntry;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Bank-facing statements: Profit & Loss, Balance Sheet and an Accounts Receivable
 * schedule, rendered from one engine so all years print in the same shape.
 *
 * A fiscal year is either *derived* — computed from live cashbook, shipment, expense,
 * payroll and investment records — or *manual*, where the balances were keyed in from
 * the accountant's books because the year predates this system holding transactions.
 * Callers do not need to know which; every method branches on FiscalPeriod::source.
 *
 * Presentation currency is USD. The cashbook is kept in GHS, so its figures are
 * translated at the fiscal period's closing rate; per-row rates are used wherever a
 * record carries one (incomes, expenses, shipments, investments all snapshot theirs).
 *
 * REVENUE RECOGNITION — the cashbook is the single source for trading revenue and
 * expenditure, deliberately. Shipment totals and the  table are NOT added to
 * the P&L: the cashbook already records the cash side of those same transactions, so
 * counting both would double every figure. The consequence is that the P&L is prepared
 * on a CASH basis (revenue when received, costs when paid) rather than an accrual one.
 * Shipments reach the statements only through accounts receivable on the balance sheet,
 * which is what carries the unbilled/unpaid accrual side.
 */
class FinancialStatementService
{
    public function __construct(protected ExchangeRateService $exchangeRateService) {}

    /**
     * @return array{year: int, period: array, source: string, currency: string, exchange_rate: float, revenue: array, cost_of_sales: array, operating_expenses: array, finance_costs: array, totals: array}
     */
    public function profitAndLoss(int $year): array
    {
        $period = $this->periodFor($year);
        $lines = $period->isDerived()
            ? $this->derivedProfitAndLossLines($period)
            : $this->manualLines($year, [AccountType::income, AccountType::expense]);

        $revenue = $this->groupByStatementLine($lines, AccountSubtype::revenue);
        $costOfSales = $this->groupByStatementLine($lines, AccountSubtype::cost_of_sales);
        $operatingExpenses = $this->groupByStatementLine($lines, AccountSubtype::operating_expense);
        $financeCosts = $this->groupByStatementLine($lines, AccountSubtype::finance_cost);

        $totalRevenue = $this->sum($revenue);
        $totalCostOfSales = $this->sum($costOfSales);
        $totalOperating = $this->sum($operatingExpenses);
        $totalFinance = $this->sum($financeCosts);

        $grossProfit = round($totalRevenue - $totalCostOfSales, 2);
        $operatingProfit = round($grossProfit - $totalOperating, 2);
        $netProfit = round($operatingProfit - $totalFinance, 2);

        return [
            'year' => $year,
            'period' => $this->periodMeta($period),
            'source' => $period->source->value,
            'currency' => 'USD',
            'exchange_rate' => $this->closingRate($period),
            'revenue' => $revenue,
            'cost_of_sales' => $costOfSales,
            'operating_expenses' => $operatingExpenses,
            'finance_costs' => $financeCosts,
            'totals' => [
                'revenue' => $totalRevenue,
                'cost_of_sales' => $totalCostOfSales,
                'gross_profit' => $grossProfit,
                'operating_expenses' => $totalOperating,
                'operating_profit' => $operatingProfit,
                'finance_costs' => $totalFinance,
                'net_profit' => $netProfit,
                'gross_margin_percentage' => $totalRevenue > 0 ? round($grossProfit / $totalRevenue * 100, 2) : 0.0,
                'net_margin_percentage' => $totalRevenue > 0 ? round($netProfit / $totalRevenue * 100, 2) : 0.0,
            ],
        ];
    }

    /**
     * @return array{year: int, as_of: string, source: string, currency: string, exchange_rate: float, assets: array, liabilities: array, equity: array, totals: array, is_balanced: bool, imbalance: float}
     */
    public function balanceSheet(int $year): array
    {
        $period = $this->periodFor($year);
        $lines = $period->isDerived()
            ? $this->derivedBalanceSheetLines($period)
            : $this->manualLines($year, [AccountType::asset, AccountType::liability, AccountType::equity]);

        $currentAssets = $this->groupByStatementLine($lines, AccountSubtype::current_asset);
        $fixedAssets = $this->groupByStatementLine($lines, AccountSubtype::fixed_asset);
        $currentLiabilities = $this->groupByStatementLine($lines, AccountSubtype::current_liability);
        $longTermLiabilities = $this->groupByStatementLine($lines, AccountSubtype::long_term_liability);
        $equity = $this->groupByStatementLine($lines, AccountSubtype::equity);

        $totalAssets = round($this->sum($currentAssets) + $this->sum($fixedAssets), 2);
        $totalLiabilities = round($this->sum($currentLiabilities) + $this->sum($longTermLiabilities), 2);
        $totalEquity = round($this->sum($equity), 2);

        // Surfaced rather than hidden: an imbalance means the underlying records do
        // not fully articulate, and whoever signs the statement needs to know that
        // before it goes to a lender.
        $imbalance = round($totalAssets - ($totalLiabilities + $totalEquity), 2);

        return [
            'year' => $year,
            'as_of' => $period->end_date->toDateString(),
            'source' => $period->source->value,
            'currency' => 'USD',
            'exchange_rate' => $this->closingRate($period),
            'assets' => [
                'current' => $currentAssets,
                'fixed' => $fixedAssets,
            ],
            'liabilities' => [
                'current' => $currentLiabilities,
                'long_term' => $longTermLiabilities,
            ],
            'equity' => $equity,
            'totals' => [
                'current_assets' => $this->sum($currentAssets),
                'fixed_assets' => $this->sum($fixedAssets),
                'total_assets' => $totalAssets,
                'current_liabilities' => $this->sum($currentLiabilities),
                'long_term_liabilities' => $this->sum($longTermLiabilities),
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
                'liabilities_and_equity' => round($totalLiabilities + $totalEquity, 2),
            ],
            'is_balanced' => abs($imbalance) < 0.01,
            'imbalance' => $imbalance,
        ];
    }

    /**
     * Ageing schedule of what clients owe, as at an explicit date.
     *
     * Ages off shipment created_at with (total - paid): the invoices table carries no
     * issue or due date of its own — only shipment_id, total_amount and status — so a
     * true invoice-date ageing is not available from the current schema. Invoices
     * contribute their status only.
     *
     * @return array{year: int, as_of: string, currency: string, aging: array, clients: array, totals: array}
     */
    public function accountsReceivable(int $year, ?Carbon $asOf = null): array
    {
        $period = $this->periodFor($year);
        $asOf ??= $period->end_date->copy();

        $shipments = Shipment::query()
            ->whereRaw('total > paid')
            ->where('created_at', '<=', $asOf->copy()->endOfDay())
            ->with('client')
            ->get();

        $invoiceStatuses = Invoice::whereIn('shipment_id', $shipments->pluck('id'))
            ->pluck('status', 'shipment_id');

        $buckets = ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0];
        $counts = ['0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        $clients = [];

        foreach ($shipments as $shipment) {
            $balance = round((float) $shipment->total - (float) $shipment->paid, 2);

            if ($balance <= 0) {
                continue;
            }

            $daysOutstanding = $shipment->created_at->diffInDays($asOf);
            $bucket = match (true) {
                $daysOutstanding <= 30 => '0-30',
                $daysOutstanding <= 60 => '31-60',
                $daysOutstanding <= 90 => '61-90',
                default => '90+',
            };

            $buckets[$bucket] += $balance;
            $counts[$bucket]++;

            $clientName = $shipment->client?->name ?? 'Unallocated';
            $clients[$clientName] ??= [
                'client' => $clientName,
                'shipment_count' => 0,
                'outstanding' => 0.0,
                'oldest_days' => 0,
                'buckets' => ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0],
            ];
            $clients[$clientName]['shipment_count']++;
            $clients[$clientName]['outstanding'] = round($clients[$clientName]['outstanding'] + $balance, 2);
            $clients[$clientName]['oldest_days'] = max($clients[$clientName]['oldest_days'], (int) $daysOutstanding);
            $clients[$clientName]['buckets'][$bucket] = round($clients[$clientName]['buckets'][$bucket] + $balance, 2);
        }

        $clients = collect($clients)->sortByDesc('outstanding')->values()->all();

        return [
            'year' => $year,
            'as_of' => $asOf->toDateString(),
            'currency' => 'USD',
            'aging' => collect($buckets)->map(fn (float $amount, string $bucket) => [
                'bucket' => $bucket,
                'count' => $counts[$bucket],
                'amount' => round($amount, 2),
            ])->values()->all(),
            'clients' => $clients,
            'totals' => [
                'outstanding' => round(array_sum($buckets), 2),
                'shipment_count' => array_sum($counts),
                'client_count' => count($clients),
                'invoiced_count' => $invoiceStatuses->count(),
            ],
        ];
    }

    // ── Derived-year mapping ────────────────────────────────────────────────

    /**
     * @return Collection<int, array{account: ChartOfAccount, amount: float}>
     */
    private function derivedProfitAndLossLines(FiscalPeriod $period): Collection
    {
        $year = $period->year;
        $rate = $this->closingRate($period);
        $accounts = $this->accountsByCode();
        $lines = collect();

        $add = function (string $code, float $amount) use ($accounts, &$lines) {
            $account = $accounts->get($code);

            if ($account && abs($amount) >= 0.01) {
                $lines->push(['account' => $account, 'amount' => round($amount, 2)]);
            }
        };

        // Revenue and expenditure from the cashbook's monthly ledgers — the real
        // accounting backbone. Kept in GHS, so translated at the closing rate.
        foreach (CashbookIncomeLedger::where('year', $year)->get()->groupBy('ledger_type.value') as $ledgerType => $rows) {
            $add('INC-'.strtoupper((string) $ledgerType), $this->ghsToUsd((float) $rows->sum('credit'), $rate));
        }

        foreach (CashbookExpenditureLedger::where('year', $year)->get()->groupBy('ledger_type.value') as $ledgerType => $rows) {
            $add('EXP-'.strtoupper((string) $ledgerType), $this->ghsToUsd((float) $rows->sum('debit'), $rate));
        }

        // Income and expense records carry their own snapshotted rate, so they are
        // summed directly in USD rather than translated at the closing rate.
        $add('INC-OTHER', (float) Income::where('status', IncomeStatus::Received)
            ->whereYear('income_date', $year)
            ->sum('amount_usd'));

        $add('EXP-SALARIES_WAGES', (float) PayrollEntry::whereHas(
            'payrollPeriod',
            fn ($query) => $query->whereYear('pay_date', $year)
        )->sum('net_salary'));

        // Cost of capital. Without these the P&L shows no funding cost at all while
        // the balance sheet carries the full investor liability.
        $add('EXP-INVESTOR-INTEREST', (float) InvestmentTransaction::where('type', InvestmentTransactionType::interest_credit->value)
            ->where('posted', true)
            ->whereYear('date', $year)
            ->sum('credit'));

        $add('EXP-LOAN-INTEREST', (float) InvestmentInterestPayout::whereIn('status', ['due', 'processing', 'paid', 'converted'])
            ->whereYear('period_end', $year)
            ->sum('amount'));

        return $lines;
    }

    /**
     * @return Collection<int, array{account: ChartOfAccount, amount: float}>
     */
    private function derivedBalanceSheetLines(FiscalPeriod $period): Collection
    {
        $year = $period->year;
        $rate = $this->closingRate($period);
        $accounts = $this->accountsByCode();
        $lines = collect();

        $add = function (string $code, float $amount) use ($accounts, &$lines) {
            $account = $accounts->get($code);

            if ($account && abs($amount) >= 0.01) {
                $lines->push(['account' => $account, 'amount' => round($amount, 2)]);
            }
        };

        // bank_balance / momo_balance are auto-computed running balances, so the last
        // entry of the year carries the closing cash position.
        $lastEntry = CashbookEntry::where('year', '<=', $year)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->first();

        $add('AST-BANK', $this->ghsToUsd((float) ($lastEntry->bank_balance ?? 0), $rate));
        $add('AST-MOMO', $this->ghsToUsd((float) ($lastEntry->momo_balance ?? 0), $rate));

        $receivables = $this->accountsReceivable($year, $period->end_date->copy());
        $add('AST-AR', (float) $receivables['totals']['outstanding']);

        $add('AST-FIXED', $this->ghsToUsd(
            (float) CashbookExpenditureLedger::where('year', '<=', $year)
                ->where('ledger_type', \App\Enums\ExpenditureLedgerType::FixedAssets->value)
                ->sum('debit'),
            $rate
        ));

        $add('AST-ACC-DEP', -$this->ghsToUsd(
            (float) CashbookExpenditureLedger::where('year', '<=', $year)
                ->where('ledger_type', \App\Enums\ExpenditureLedgerType::Depreciation->value)
                ->sum('debit'),
            $rate
        ));

        $add('AST-WAREHOUSE-WIP', $this->ghsToUsd(
            (float) CashbookExpenditureLedger::where('year', '<=', $year)
                ->where('ledger_type', \App\Enums\ExpenditureLedgerType::WarehouseWip->value)
                ->sum('debit'),
            $rate
        ));

        // Investor capital. excludingConverted() matters here: a tranche settled into a
        // successor still exists as a row, and counting both would overstate what the
        // company owes by the full converted amount.
        $liveInvestments = Investment::query()
            ->excludingConverted()
            ->whereIn('status', [InvestmentStatus::active->value, InvestmentStatus::pending_payment->value])
            ->whereDate('start_date', '<=', $period->end_date)
            ->get();

        $add('LIA-INVESTOR-CAPITAL', (float) $liveInvestments
            ->where('capital_type', InvestmentCapitalType::investment)
            ->sum('current_balance'));

        $add('LIA-INVESTOR-LOANS', (float) $liveInvestments
            ->where('capital_type', InvestmentCapitalType::loan)
            ->sum('principal_amount'));

        $add('LIA-INVESTOR-INTEREST', (float) InvestmentInterestPayout::whereIn('investment_id', $liveInvestments->pluck('id'))
            ->whereIn('status', ['due', 'processing'])
            ->get()
            ->sum(fn ($payout) => (float) $payout->amount - (float) $payout->amount_paid));

        $add('LIA-DIRECTOR', $this->ghsToUsd(
            (float) (CashbookDirectorAccount::orderByDesc('date')->orderByDesc('created_at')->first()?->cl_balance ?? 0),
            $rate
        ));

        $add('LIA-LOANS', $this->ghsToUsd(
            (float) CashbookLoan::query()
                ->orderByDesc('date')
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('lender_name')
                ->sum(fn (Collection $rows) => (float) $rows->first()->cl_balance),
            $rate
        ));

        $add('LIA-WHT', $this->ghsToUsd(
            (float) CashbookWithholdingTax::where('year', $year)->sum('wht_amount'),
            $rate
        ));

        // Retained earnings roll forward: whatever prior years closed at, plus this
        // year's result. Prior years come from account_balances, which is where a
        // manually keyed year records its own closing equity.
        $priorRetained = (float) AccountBalance::where('fiscal_year', '<', $year)
            ->whereHas('account', fn ($query) => $query->where('code', 'EQY-RETAINED'))
            ->orderByDesc('fiscal_year')
            ->first()?->closing_balance;

        $add('EQY-RETAINED', round($priorRetained + $this->profitAndLossNetOnly($period), 2));

        $add('EQY-CAPITAL', (float) AccountBalance::where('fiscal_year', '<=', $year)
            ->whereHas('account', fn ($query) => $query->where('code', 'EQY-CAPITAL'))
            ->orderByDesc('fiscal_year')
            ->first()?->closing_balance);

        return $lines;
    }

    /**
     * Net profit for a derived year, without re-entering balanceSheet() — used by the
     * retained-earnings roll-forward above.
     */
    private function profitAndLossNetOnly(FiscalPeriod $period): float
    {
        $lines = $this->derivedProfitAndLossLines($period);

        $income = $lines
            ->filter(fn (array $line) => $line['account']->type === AccountType::income)
            ->sum('amount');

        $expense = $lines
            ->filter(fn (array $line) => $line['account']->type === AccountType::expense)
            ->sum('amount');

        return round($income - $expense, 2);
    }

    // ── Manual-year mapping ─────────────────────────────────────────────────

    /**
     * @param  array<int, AccountType>  $types
     * @return Collection<int, array{account: ChartOfAccount, amount: float}>
     */
    private function manualLines(int $year, array $types): Collection
    {
        $period = $this->periodFor($year);

        // The accountant's Ghana books are kept in GHS while these statements present
        // in USD. Reading the keyed figures verbatim would overstate the year by the
        // exchange rate and label the result USD — on a document going to a lender.
        $divisor = $period->needsTranslation() ? $this->closingRate($period) : 1.0;

        if ($divisor <= 0) {
            $divisor = 1.0;
        }

        return AccountBalance::where('fiscal_year', $year)
            ->with('account')
            ->get()
            ->filter(fn (AccountBalance $balance) => $balance->account
                && in_array($balance->account->type, $types, true))
            ->map(fn (AccountBalance $balance) => [
                'account' => $balance->account,
                // A P&L account's figure for the year is its movement; a balance-sheet
                // account's is where it stood at the year end.
                'amount' => round((float) ($balance->account->type->isBalanceSheet()
                    ? $balance->closing_balance
                    : $balance->movement) / $divisor, 2),
            ])
            ->values();
    }

    /**
     * Whether a manually keyed year articulates: total debits must equal total credits.
     *
     * @return array{debits: float, credits: float, difference: float, balances: bool}
     */
    public function trialBalanceCheck(int $year): array
    {
        $balances = AccountBalance::where('fiscal_year', $year)->with('account')->get();

        $debits = $balances
            ->filter(fn (AccountBalance $balance) => $balance->account?->type->isDebitNormal())
            ->sum('closing_balance');

        $credits = $balances
            ->reject(fn (AccountBalance $balance) => $balance->account?->type->isDebitNormal())
            ->sum('closing_balance');

        $difference = round((float) $debits - (float) $credits, 2);

        return [
            'debits' => round((float) $debits, 2),
            'credits' => round((float) $credits, 2),
            'difference' => $difference,
            'balances' => abs($difference) < 0.01,
        ];
    }

    // ── Shared helpers ──────────────────────────────────────────────────────

    public function periodFor(int $year): FiscalPeriod
    {
        return FiscalPeriod::firstOrCreate(
            ['year' => $year],
            [
                'start_date' => Carbon::create($year, 1, 1)->toDateString(),
                'end_date' => Carbon::create($year, 12, 31)->toDateString(),
                // Years the system actually holds records for are derived; anything
                // earlier has to be keyed in from the accountant's books.
                'source' => $year >= self::firstRecordedYear()
                    ? FiscalPeriodSource::derived->value
                    : FiscalPeriodSource::manual->value,
            ]
        );
    }

    /**
     * The earliest year this system holds primary records for. Anything before it
     * cannot be derived and must be entered manually.
     */
    public static function firstRecordedYear(): int
    {
        return (int) config('financials.first_recorded_year', 2026);
    }

    /**
     * @return Collection<string, ChartOfAccount>
     */
    private function accountsByCode(): Collection
    {
        return ChartOfAccount::active()->get()->keyBy('code');
    }

    /**
     * @param  Collection<int, array{account: ChartOfAccount, amount: float}>  $lines
     * @return array<int, array{statement_line: string, amount: float, accounts: array}>
     */
    private function groupByStatementLine(Collection $lines, AccountSubtype $subtype): array
    {
        return $lines
            ->filter(fn (array $line) => $line['account']->subtype === $subtype)
            ->groupBy(fn (array $line) => $line['account']->statement_line)
            ->map(fn (Collection $group, string $statementLine) => [
                'statement_line' => $statementLine,
                'amount' => round($group->sum('amount'), 2),
                'accounts' => $group
                    ->sortBy(fn (array $line) => $line['account']->sort_order)
                    ->map(fn (array $line) => [
                        'code' => $line['account']->code,
                        'name' => $line['account']->name,
                        'amount' => $line['amount'],
                    ])->values()->all(),
            ])
            ->sortBy('statement_line')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{amount: float}>  $group
     */
    private function sum(array $group): float
    {
        return round(collect($group)->sum('amount'), 2);
    }

    private function ghsToUsd(float $amountGhs, float $rate): float
    {
        return $rate > 0 ? round($amountGhs / $rate, 2) : 0.0;
    }

    /**
     * GHS per USD at the year end. Pinned on the fiscal period once set, so a
     * statement reprinted later reproduces the figures it was signed off with.
     */
    private function closingRate(FiscalPeriod $period): float
    {
        if ($period->closing_exchange_rate) {
            return (float) $period->closing_exchange_rate;
        }

        try {
            $rate = (float) $this->exchangeRateService->getCurrentRate('USD', 'GHS');
        } catch (\Throwable $e) {
            $rate = 12.0;
        }

        $period->update(['closing_exchange_rate' => $rate]);

        return $rate;
    }

    /**
     * @return array{start: string, end: string, label: string}
     */
    private function periodMeta(FiscalPeriod $period): array
    {
        return [
            'start' => $period->start_date->toDateString(),
            'end' => $period->end_date->toDateString(),
            'label' => 'Year ended '.$period->end_date->format('F j, Y'),
        ];
    }
}

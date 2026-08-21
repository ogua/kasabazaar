<?php

namespace Database\Seeders;

use App\Enums\AccountSubtype;
use App\Enums\ExpenditureLedgerType;
use App\Enums\IncomeLedgerType;
use App\Models\ChartOfAccount;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use Illuminate\Database\Seeder;

/**
 * The chart of accounts is derived from how the business already classifies money:
 * the cashbook's income and expenditure ledger types map one-for-one onto P&L
 * accounts, and the balance-sheet accounts are added on top since no expense
 * analogue exists for them.
 *
 * Idempotent — safe to re-run after adding a ledger type.
 */
class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 0;

        foreach ($this->balanceSheetAccounts() as $code => $definition) {
            $this->upsert($code, $definition[0], $definition[1], $definition[2], $sort += 10);
        }

        // Revenue, from the cashbook's income ledger types.
        foreach (IncomeLedgerType::cases() as $ledgerType) {
            $this->upsert(
                'INC-'.strtoupper($ledgerType->value),
                $ledgerType->getLabel(),
                AccountSubtype::revenue,
                'Revenue',
                $sort += 10
            );
        }

        $this->upsert('INC-OTHER', 'Other Income', AccountSubtype::revenue, 'Revenue', $sort += 10);

        // Expenses, from the cashbook's expenditure ledger types. A few are not
        // operating costs and are classified accordingly so the P&L subtotals mean
        // something: shipping/import is cost of sales, bank charges are a finance cost,
        // and fixed-asset spend is capitalised rather than expensed.
        foreach (ExpenditureLedgerType::cases() as $ledgerType) {
            $this->upsert(
                'EXP-'.strtoupper($ledgerType->value),
                $ledgerType->getLabel(),
                $this->subtypeFor($ledgerType),
                $this->statementLineFor($ledgerType),
                $sort += 10
            );
        }

        // Investor capital is not free: interest credited to compounding tranches and
        // cash interest paid on loan tranches are both a cost of funding the business.
        // Without these the P&L would show no cost of capital at all while the balance
        // sheet carries the investor liability, which any lender will query.
        $this->upsert('EXP-INVESTOR-INTEREST', 'Investor Interest — Compounding', AccountSubtype::finance_cost, 'Finance Costs', $sort += 10);
        $this->upsert('EXP-LOAN-INTEREST', 'Investor Interest — Loans', AccountSubtype::finance_cost, 'Finance Costs', $sort += 10);

        // Trading accounts. The cashbook ledger types above cover the cashbook-based
        // path; these cover the shipment/expense records the business actually runs on.
        foreach ($this->tradingAccounts() as $code => $definition) {
            $this->upsert($code, $definition[0], $definition[1], $definition[2], $sort += 10);
        }

        $this->mapCategories();
    }

    /**
     * @return array<string, array{0: string, 1: AccountSubtype, 2: string}>
     */
    private function tradingAccounts(): array
    {
        return [
            'INC-FREIGHT' => ['Freight & Shipping Revenue', AccountSubtype::revenue, 'Revenue'],
            'INC-SERVICE' => ['Service & Handling Fees', AccountSubtype::revenue, 'Revenue'],

            'EXP-CUSTOMS' => ['Customs & Duties', AccountSubtype::cost_of_sales, 'Cost of Sales'],
            'EXP-PORT' => ['Port Charges', AccountSubtype::cost_of_sales, 'Cost of Sales'],
            'EXP-STORAGE' => ['Storage & Warehousing', AccountSubtype::cost_of_sales, 'Cost of Sales'],
            'EXP-DOCUMENTATION' => ['Documentation', AccountSubtype::cost_of_sales, 'Cost of Sales'],
            'EXP-PACKAGING' => ['Packaging', AccountSubtype::cost_of_sales, 'Cost of Sales'],
            'EXP-AGENT' => ['Agent Fees', AccountSubtype::cost_of_sales, 'Cost of Sales'],
            'EXP-INSURANCE' => ['Insurance', AccountSubtype::operating_expense, 'Operating Expenses'],
            'EXP-FUEL' => ['Fuel', AccountSubtype::operating_expense, 'Operating Expenses'],
            'EXP-MAINTENANCE' => ['Vehicle & Equipment Maintenance', AccountSubtype::operating_expense, 'Operating Expenses'],
            'EXP-MISC' => ['Other Operating Costs', AccountSubtype::operating_expense, 'Operating Expenses'],
        ];
    }

    /**
     * Point each seeded expense/income category at the account it reports under, so
     * the statement engine reads the mapping rather than hardcoding category names.
     * Only fills a category that has not been mapped by hand — a deliberate mapping
     * made in the admin panel is never overwritten by re-running this seeder.
     */
    private function mapCategories(): void
    {
        $expenseMap = [
            'CUSTOMS' => 'EXP-CUSTOMS',
            'TRANSPORT' => 'EXP-TRANSPORTATION',
            'STORAGE' => 'EXP-STORAGE',
            'DOCUMENTATION' => 'EXP-DOCUMENTATION',
            'INSURANCE' => 'EXP-INSURANCE',
            'PORT' => 'EXP-PORT',
            'PACKAGING' => 'EXP-PACKAGING',
            'AGENT' => 'EXP-AGENT',
            'FUEL' => 'EXP-FUEL',
            'MAINTENANCE' => 'EXP-MAINTENANCE',
            'COMMUNICATION' => 'EXP-TELEPHONE_INTERNET',
            'MISC' => 'EXP-MISC',
        ];

        $incomeMap = [
            'STORAGE' => 'INC-SERVICE',
            'DOCUMENTATION' => 'INC-SERVICE',
            'REPACKAGING' => 'INC-SERVICE',
            'HANDLING' => 'INC-SERVICE',
            'LATE_FEES' => 'INC-SERVICE',
            'CONSULTING' => 'INC-OTHER',
            'RENTAL' => 'INC-OTHER',
            'COMMISSION' => 'INC-OTHER',
            'INTEREST' => 'INC-OTHER',
            'INSURANCE_REFUND' => 'INC-OTHER',
            'CUSTOMS_REFUND' => 'INC-OTHER',
            'OTHER' => 'INC-OTHER',
        ];

        $accounts = ChartOfAccount::pluck('id', 'code');

        foreach ($expenseMap as $categoryCode => $accountCode) {
            ExpenseCategory::where('code', $categoryCode)
                ->whereNull('chart_of_account_id')
                ->update(['chart_of_account_id' => $accounts[$accountCode] ?? null]);
        }

        foreach ($incomeMap as $categoryCode => $accountCode) {
            IncomeCategory::where('code', $categoryCode)
                ->whereNull('chart_of_account_id')
                ->update(['chart_of_account_id' => $accounts[$accountCode] ?? null]);
        }
    }

    /**
     * @return array<string, array{0: string, 1: AccountSubtype, 2: string}>
     */
    private function balanceSheetAccounts(): array
    {
        return [
            'AST-BANK' => ['Bank', AccountSubtype::current_asset, 'Cash & Bank'],
            'AST-MOMO' => ['Mobile Money', AccountSubtype::current_asset, 'Cash & Bank'],
            'AST-AR' => ['Accounts Receivable', AccountSubtype::current_asset, 'Trade Receivables'],
            'AST-INVENTORY' => ['Inventory', AccountSubtype::current_asset, 'Inventory'],
            'AST-WAREHOUSE-WIP' => ['Warehouse Work in Progress', AccountSubtype::current_asset, 'Inventory'],
            'AST-PREPAID' => ['Prepayments', AccountSubtype::current_asset, 'Prepayments'],
            'AST-FIXED' => ['Property, Plant & Equipment', AccountSubtype::fixed_asset, 'Property, Plant & Equipment'],
            'AST-VEHICLES' => ['Motor Vehicles', AccountSubtype::fixed_asset, 'Property, Plant & Equipment'],
            'AST-ACC-DEP' => ['Accumulated Depreciation', AccountSubtype::fixed_asset, 'Property, Plant & Equipment'],

            'LIA-AP' => ['Accounts Payable', AccountSubtype::current_liability, 'Trade Payables'],
            'LIA-WHT' => ['Withholding Tax Payable', AccountSubtype::current_liability, 'Statutory Liabilities'],
            'LIA-PAYE' => ['PAYE Payable', AccountSubtype::current_liability, 'Statutory Liabilities'],
            'LIA-SSNIT' => ['SSNIT Payable', AccountSubtype::current_liability, 'Statutory Liabilities'],
            'LIA-DIRECTOR' => ['Director Account', AccountSubtype::current_liability, 'Director & Related Parties'],
            'LIA-INVESTOR-CAPITAL' => ['Investor Capital — Compounding', AccountSubtype::long_term_liability, 'Investor Capital'],
            'LIA-INVESTOR-LOANS' => ['Investor Capital — Loans', AccountSubtype::long_term_liability, 'Investor Capital'],
            'LIA-INVESTOR-INTEREST' => ['Accrued Investor Interest', AccountSubtype::current_liability, 'Investor Capital'],
            'LIA-LOANS' => ['Bank & Third-Party Loans', AccountSubtype::long_term_liability, 'Borrowings'],

            'EQY-CAPITAL' => ['Share Capital', AccountSubtype::equity, 'Share Capital'],
            'EQY-RETAINED' => ['Retained Earnings', AccountSubtype::equity, 'Retained Earnings'],
        ];
    }

    private function subtypeFor(ExpenditureLedgerType $ledgerType): AccountSubtype
    {
        return match ($ledgerType) {
            ExpenditureLedgerType::ShippingImport,
            ExpenditureLedgerType::Material,
            ExpenditureLedgerType::Workmanship,
            ExpenditureLedgerType::Transportation => AccountSubtype::cost_of_sales,

            ExpenditureLedgerType::BankCharges => AccountSubtype::finance_cost,

            // Capitalised, not expensed — it lands on the balance sheet via AST-FIXED.
            ExpenditureLedgerType::FixedAssets => AccountSubtype::fixed_asset,

            default => AccountSubtype::operating_expense,
        };
    }

    private function statementLineFor(ExpenditureLedgerType $ledgerType): string
    {
        return match ($this->subtypeFor($ledgerType)) {
            AccountSubtype::cost_of_sales => 'Cost of Sales',
            AccountSubtype::finance_cost => 'Finance Costs',
            AccountSubtype::fixed_asset => 'Property, Plant & Equipment',
            default => 'Operating Expenses',
        };
    }

    private function upsert(string $code, string $name, AccountSubtype $subtype, string $statementLine, int $sortOrder): void
    {
        ChartOfAccount::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'type' => $subtype->type()->value,
                'subtype' => $subtype->value,
                'statement_line' => $statementLine,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]
        );
    }
}

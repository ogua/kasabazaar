<?php

namespace App\Exports;

use Illuminate\Support\Collection;

class ProfitAndLossExport extends FinancialStatementExport
{
    protected function reportTitle(): string
    {
        return 'Statement of Profit or Loss';
    }

    protected function reportPeriod(): string
    {
        return $this->statement['period']['label'];
    }

    protected function rows(): Collection
    {
        $totals = $this->statement['totals'];

        return collect()
            ->push(['REVENUE', null, true])
            ->concat($this->groupRows($this->statement['revenue']))
            ->push(['Total Revenue', $totals['revenue'], true])
            ->push(['', null, false])

            ->push(['COST OF SALES', null, true])
            ->concat($this->groupRows($this->statement['cost_of_sales']))
            ->push(['Total Cost of Sales', $totals['cost_of_sales'], true])
            ->push(['Gross Profit', $totals['gross_profit'], true])
            ->push(['', null, false])

            ->push(['OPERATING EXPENSES', null, true])
            ->concat($this->groupRows($this->statement['operating_expenses']))
            ->push(['Total Operating Expenses', $totals['operating_expenses'], true])
            ->push(['Operating Profit', $totals['operating_profit'], true])
            ->push(['', null, false])

            // Kept as its own caption rather than folded into operating expenses: this
            // is the cost of the investor capital carried on the balance sheet.
            ->push(['FINANCE COSTS', null, true])
            ->concat($this->groupRows($this->statement['finance_costs']))
            ->push(['Total Finance Costs', $totals['finance_costs'], true])
            ->push(['', null, false])

            ->push(['PROFIT FOR THE YEAR', $totals['net_profit'], true])
            ->push(['', null, false])
            ->push(['Gross margin %', $totals['gross_margin_percentage'], false])
            ->push(['Net margin %', $totals['net_margin_percentage'], false]);
    }
}

<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class BalanceSheetExport extends FinancialStatementExport
{
    protected function reportTitle(): string
    {
        return 'Statement of Financial Position';
    }

    protected function reportPeriod(): string
    {
        return 'As at '.Carbon::parse($this->statement['as_of'])->format('F j, Y');
    }

    protected function rows(): Collection
    {
        $totals = $this->statement['totals'];
        $rows = collect();

        // An imbalance rides at the top of the sheet, not buried at the bottom —
        // whoever opens this needs to see it before reading any figure below.
        if (! $this->statement['is_balanced']) {
            $rows->push(['DOES NOT BALANCE — assets differ from liabilities plus equity by', $this->statement['imbalance'], true]);
            $rows->push(['', null, false]);
        }

        return $rows
            ->push(['CURRENT ASSETS', null, true])
            ->concat($this->groupRows($this->statement['assets']['current']))
            ->push(['Total Current Assets', $totals['current_assets'], true])
            ->push(['', null, false])

            ->push(['NON-CURRENT ASSETS', null, true])
            ->concat($this->groupRows($this->statement['assets']['fixed']))
            ->push(['Total Non-Current Assets', $totals['fixed_assets'], true])
            ->push(['TOTAL ASSETS', $totals['total_assets'], true])
            ->push(['', null, false])

            ->push(['CURRENT LIABILITIES', null, true])
            ->concat($this->groupRows($this->statement['liabilities']['current']))
            ->push(['Total Current Liabilities', $totals['current_liabilities'], true])
            ->push(['', null, false])

            ->push(['NON-CURRENT LIABILITIES', null, true])
            ->concat($this->groupRows($this->statement['liabilities']['long_term']))
            ->push(['Total Non-Current Liabilities', $totals['long_term_liabilities'], true])
            ->push(['Total Liabilities', $totals['total_liabilities'], true])
            ->push(['', null, false])

            ->push(['EQUITY', null, true])
            ->concat($this->groupRows($this->statement['equity']))
            ->push(['Total Equity', $totals['total_equity'], true])
            ->push(['', null, false])

            ->push(['TOTAL LIABILITIES & EQUITY', $totals['liabilities_and_equity'], true]);
    }
}

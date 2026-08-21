<?php

namespace App\Filament\Pages;

use App\Enums\CashbookCostCenter;
use App\Exports\CashbookExport;
use App\Models\CashbookEntry;
use App\Models\CashbookWithholdingTax;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class CashbookReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Cashbook';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Cashbook Report';

    protected static ?string $navigationLabel = 'Report & Export';

    protected static string $view = 'filament.pages.cashbook-report';

    public int $selectedMonth;

    public int $selectedYear;

    public function mount(): void
    {
        $this->selectedMonth = (int) now()->format('n');
        $this->selectedYear = (int) now()->format('Y');
    }

    // ─── Header Actions ───────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action('downloadPdf'),

            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action('downloadExcel'),
        ];
    }

    // ─── Downloads ────────────────────────────────────────────────────────────

    public function downloadPdf()
    {
        $data = $this->getReportData();

        $pdf = Pdf::loadView('reports.cashbook-pdf', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'cashbook-'.strtolower($data['monthName']).'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    public function downloadExcel()
    {
        $monthName = date('M-Y', mktime(0, 0, 0, $this->selectedMonth, 1, $this->selectedYear));
        $filename = 'cashbook-'.strtolower($monthName).'.xlsx';

        return Excel::download(
            new CashbookExport($this->selectedMonth, $this->selectedYear),
            $filename
        );
    }

    // ─── Report Data ──────────────────────────────────────────────────────────

    public function getReportData(): array
    {
        $month = $this->selectedMonth;
        $year = $this->selectedYear;

        $monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        $totals = CashbookEntry::monthlyTotals($month, $year);

        // Opening & closing balances
        $entries = CashbookEntry::query()
            ->forMonth($month, $year)
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $firstEntry = $entries->first();
        $lastEntry = $entries->last();

        $openingBank = $firstEntry?->bank_balance - $firstEntry?->bank_debit + $firstEntry?->bank_credit ?? 0;
        $openingMomo = $firstEntry?->momo_balance - $firstEntry?->momo_debit + $firstEntry?->momo_credit ?? 0;

        // For OP_BALANCE entries the opening IS the first row's balance value
        if ($firstEntry && $firstEntry->cost_center === CashbookCostCenter::OpBalance) {
            $openingBank = (float) $firstEntry->bank_balance;
            $openingMomo = (float) $firstEntry->momo_balance;
        }

        $closingBank = (float) ($lastEntry?->bank_balance ?? 0);
        $closingMomo = (float) ($lastEntry?->momo_balance ?? 0);

        // Receipts breakdown
        $receiptsBreakdown = [
            'Opening Balance' => (float) ($totals['op_balance'] ?? 0),
            'Sales' => (float) ($totals['sales'] ?? 0),
            'Director Transfer' => (float) ($totals['dir_transfer'] ?? 0),
            'Shipping Fee' => (float) ($totals['shipping_fee'] ?? 0),
            'Service Fee' => (float) ($totals['service_fee'] ?? 0),
            'MOMO Interest' => (float) ($totals['momo_interest'] ?? 0),
            'Property Management' => (float) ($totals['property_management'] ?? 0),
            'Refund' => (float) ($totals['refund'] ?? 0),
            'Contra (Receipt)' => (float) ($totals['contra_receipt'] ?? 0),
        ];
        $totalReceipts = array_sum($receiptsBreakdown);

        // Expenditures breakdown
        $expendituresBreakdown = [
            'Import Duty Charges' => (float) ($totals['import_duty'] ?? 0),
            'Shipping Expenses' => (float) ($totals['shipping_expenses'] ?? 0),
            'Salaries & Wages' => (float) ($totals['salaries_wages'] ?? 0),
            'Bank Charges' => (float) ($totals['bank_charges'] ?? 0),
            'SSNIT' => (float) ($totals['ssnit'] ?? 0),
            'PAYE' => (float) ($totals['paye'] ?? 0),
            'Withholding Tax' => (float) ($totals['withholding_tax'] ?? 0),
            'MOMO Charges' => (float) ($totals['momo_charges'] ?? 0),
            'Contra (Payment)' => (float) ($totals['contra_payment'] ?? 0),
            'Transportation' => (float) ($totals['transportation'] ?? 0),
            'Materials' => (float) ($totals['materials'] ?? 0),
            'Donation' => (float) ($totals['donation'] ?? 0),
        ];
        $totalExpenditures = array_sum($expendituresBreakdown);

        // WHT breakdown for this month
        $whtEntries = CashbookWithholdingTax::query()
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->map(fn ($w) => [
                'staff_name' => $w->staff_name,
                'gross_amount' => (float) $w->gross_amount,
                'rate' => (float) $w->rate,
                'wht_amount' => (float) $w->wht_amount,
            ])
            ->toArray();

        // Formatted entries for view/PDF
        $formattedEntries = $entries->map(fn ($e) => [
            'date' => $e->date->format('Y-m-d'),
            'pv_no' => $e->pv_no,
            'details' => $e->details,
            'chq_ref' => $e->chq_ref,
            'bank_debit' => (float) $e->bank_debit,
            'momo_debit' => (float) $e->momo_debit,
            'bank_credit' => (float) $e->bank_credit,
            'momo_credit' => (float) $e->momo_credit,
            'bank_balance' => (float) $e->bank_balance,
            'momo_balance' => (float) $e->momo_balance,
            'cost_center_label' => $e->cost_center->getLabel(),
            'is_receipt' => $e->cost_center->isReceipt(),
        ])->toArray();

        return [
            'monthName' => $monthName,
            'month' => $month,
            'year' => $year,
            'entries' => $formattedEntries,
            'totals' => $totals,
            'whtEntries' => $whtEntries,
            'summary' => [
                'opening_bank' => $openingBank,
                'opening_momo' => $openingMomo,
                'closing_bank' => $closingBank,
                'closing_momo' => $closingMomo,
                'total_receipts' => $totalReceipts,
                'total_expenditures' => $totalExpenditures,
                'net_position' => round($totalReceipts - $totalExpenditures, 2),
                'entry_count' => $entries->count(),
                'receipts_breakdown' => $receiptsBreakdown,
                'expenditures_breakdown' => $expendituresBreakdown,
            ],
        ];
    }

    public static function getMonthOptions(): array
    {
        return [
            1 => 'January', 2 => 'February', 3 => 'March',
            4 => 'April',   5 => 'May',      6 => 'June',
            7 => 'July',    8 => 'August',   9 => 'September',
            10 => 'October', 11 => 'November', 12 => 'December',
        ];
    }

    public static function getYearOptions(): array
    {
        $y = (int) now()->format('Y');

        return array_combine(range($y - 1, $y + 1), range($y - 1, $y + 1));
    }
}

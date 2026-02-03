<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\ExpenseStatsWidget;
use App\Filament\Widgets\ExpensesByCategoryChart;
use Filament\Actions\Action;
use App\Exports\ExpensesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ExpenseReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $view = 'filament.pages.expense-report';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Expense Report';

    protected static ?string $navigationLabel = 'Expense Report';

    public ?string $start_date = null;
    public ?string $end_date = null;

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

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

    public function downloadPdf()
    {
        $data = $this->getViewData();

        $pdf = Pdf::loadView('reports.expense-pdf', [
            'expenses' => $data['expenses'],
            'summary' => $data['summary'],
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'expense-report-' . $this->start_date . '-to-' . $this->end_date . '.pdf');
    }

    public function downloadExcel()
    {
        return Excel::download(
            new ExpensesExport($this->start_date, $this->end_date),
            'expense-report-' . $this->start_date . '-to-' . $this->end_date . '.xlsx'
        );
    }

    public function getViewData(): array
    {
        return [
            'expenses' => $this->getExpenseData(),
            'summary' => $this->getExpenseSummary(),
        ];
    }

    protected function getExpenseData(): array
    {
        $query = \App\Models\Expense::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->orderBy('expense_date', 'desc');

        if ($this->start_date && $this->end_date) {
            $query->whereBetween('expense_date', [$this->start_date, $this->end_date]);
        }

        $expenses = $query->get()
            ->map(function ($expense) {
                return [
                    'id' => $expense->id,
                    'reference' => $expense->reference,
                    'date' => $expense->expense_date,
                    'category' => $expense->category?->name ?? 'Uncategorized',
                    'description' => $expense->description,
                    'amount_usd' => $expense->amount_usd,
                    'amount_ghs' => $expense->amount_ghs,
                    'exchange_rate' => $expense->exchange_rate,
                    'branch' => $expense->branch?->name ?? 'N/A',
                    'shipment_ref' => $expense->shipment?->shipping_reference ?? 'General',
                    'recorded_by' => $expense->recordedBy?->name ?? 'System',
                    'expense_stage' => $expense->expense_stage?->value ?? 'N/A',
                ];
            })
            ->toArray();

        return $expenses;
    }

    protected function getExpenseSummary(): array
    {
        $startDate = $this->start_date ?: now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->end_date ?: now()->format('Y-m-d');

        $thisMonthExpenses = \App\Models\Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount_ghs');
        $thisMonthExpensesUsd = \App\Models\Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount_usd');

        // Calculate previous period (same duration)
        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate));
        $prevEndDate = \Carbon\Carbon::parse($startDate)->subDay()->format('Y-m-d');
        $prevStartDate = \Carbon\Carbon::parse($prevEndDate)->subDays($days)->format('Y-m-d');

        $lastMonthExpenses = \App\Models\Expense::whereBetween('expense_date', [$prevStartDate, $prevEndDate])->sum('amount_ghs');
        $lastMonthExpensesUsd = \App\Models\Expense::whereBetween('expense_date', [$prevStartDate, $prevEndDate])->sum('amount_usd');

        $growth = $lastMonthExpenses > 0 ? (($thisMonthExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100 : 0;

        // By category
        $byCategory = \App\Models\Expense::with('category')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($e) => $e->category?->name ?? 'Uncategorized')
            ->map(fn($group) => [
                'count' => $group->count(),
                'total_usd' => $group->sum('amount_usd'),
                'total_ghs' => $group->sum('amount_ghs'),
            ])
            ->sortByDesc('total_ghs')
            ->toArray();

        // By stage
        $byStage = \App\Models\Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($e) => $e->expense_stage?->value ?? 'N/A')
            ->map(fn($group) => [
                'count' => $group->count(),
                'total_usd' => $group->sum('amount_usd'),
                'total_ghs' => $group->sum('amount_ghs'),
            ])
            ->toArray();

        return [
            'this_month_ghs' => $thisMonthExpenses,
            'last_month_ghs' => $lastMonthExpenses,
            'this_month_usd' => $thisMonthExpensesUsd,
            'last_month_usd' => $lastMonthExpensesUsd,
            'growth_percent' => $growth,
            'by_category' => $byCategory,
            'by_stage' => $byStage,
            'total_count' => \App\Models\Expense::whereBetween('expense_date', [$startDate, $endDate])->count(),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExpenseStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ExpensesByCategoryChart::class,
        ];
    }
}

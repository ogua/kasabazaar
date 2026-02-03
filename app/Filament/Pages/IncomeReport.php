<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\IncomeStatsWidget;
use Filament\Actions\Action;
use App\Exports\IncomesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class IncomeReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $view = 'filament.pages.income-report';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Income Report';

    protected static ?string $navigationLabel = 'Income Report';

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

        $pdf = Pdf::loadView('reports.income-pdf', [
            'incomes' => $data['incomes'],
            'summary' => $data['summary'],
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'income-report-' . $this->start_date . '-to-' . $this->end_date . '.pdf');
    }

    public function downloadExcel()
    {
        return Excel::download(
            new IncomesExport($this->start_date, $this->end_date),
            'income-report-' . $this->start_date . '-to-' . $this->end_date . '.xlsx'
        );
    }

    public function getViewData(): array
    {
        return [
            'incomes' => $this->getIncomeData(),
            'summary' => $this->getIncomeSummary(),
        ];
    }

    protected function getIncomeData(): array
    {
        $query = \App\Models\Income::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->orderBy('income_date', 'desc');

        if ($this->start_date && $this->end_date) {
            $query->whereBetween('income_date', [$this->start_date, $this->end_date]);
        }

        $incomes = $query->get()
            ->map(function ($income) {
                return [
                    'id' => $income->id,
                    'reference' => $income->reference,
                    'date' => $income->income_date,
                    'category' => $income->category?->name ?? 'Uncategorized',
                    'description' => $income->description,
                    'amount_usd' => $income->amount_usd,
                    'amount_ghs' => $income->amount_ghs,
                    'exchange_rate' => $income->exchange_rate,
                    'branch' => $income->branch?->name ?? 'N/A',
                    'shipment_ref' => $income->shipment?->shipping_reference ?? 'External',
                    'recorded_by' => $income->recordedBy?->name ?? 'System',
                    'status' => $income->status?->value ?? 'N/A',
                    'payment_method' => $income->payment_method?->value ?? 'N/A',
                ];
            })
            ->toArray();

        return $incomes;
    }

    protected function getIncomeSummary(): array
    {
        $startDate = $this->start_date ?: now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->end_date ?: now()->format('Y-m-d');

        $thisMonthIncome = \App\Models\Income::whereBetween('income_date', [$startDate, $endDate])
            ->where('status', \App\Enums\IncomeStatus::Received)
            ->sum('amount_ghs');

        $thisMonthIncomeUsd = \App\Models\Income::whereBetween('income_date', [$startDate, $endDate])
            ->where('status', \App\Enums\IncomeStatus::Received)
            ->sum('amount_usd');

        // Calculate previous period
        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate));
        $prevEndDate = \Carbon\Carbon::parse($startDate)->subDay()->format('Y-m-d');
        $prevStartDate = \Carbon\Carbon::parse($prevEndDate)->subDays($days)->format('Y-m-d');

        $lastMonthIncome = \App\Models\Income::whereBetween('income_date', [$prevStartDate, $prevEndDate])
            ->where('status', \App\Enums\IncomeStatus::Received)
            ->sum('amount_ghs');

        $lastMonthIncomeUsd = \App\Models\Income::whereBetween('income_date', [$prevStartDate, $prevEndDate])
            ->where('status', \App\Enums\IncomeStatus::Received)
            ->sum('amount_usd');

        $growth = $lastMonthIncome > 0 ? (($thisMonthIncome - $lastMonthIncome) / $lastMonthIncome) * 100 : 0;

        // By category
        $byCategory = \App\Models\Income::with('category')
            ->whereBetween('income_date', [$startDate, $endDate])
            ->where('status', \App\Enums\IncomeStatus::Received)
            ->get()
            ->groupBy(fn($i) => $i->category?->name ?? 'Uncategorized')
            ->map(fn($group) => [
                'count' => $group->count(),
                'total_usd' => $group->sum('amount_usd'),
                'total_ghs' => $group->sum('amount_ghs'),
            ])
            ->sortByDesc('total_ghs')
            ->toArray();

        // By payment method
        $byMethod = \App\Models\Income::whereBetween('income_date', [$startDate, $endDate])
            ->where('status', \App\Enums\IncomeStatus::Received)
            ->get()
            ->groupBy(fn($i) => $i->payment_method?->value ?? 'N/A')
            ->map(fn($group) => [
                'count' => $group->count(),
                'total_usd' => $group->sum('amount_usd'),
                'total_ghs' => $group->sum('amount_ghs'),
            ])
            ->toArray();

        return [
            'this_month_ghs' => $thisMonthIncome,
            'last_month_ghs' => $lastMonthIncome,
            'this_month_usd' => $thisMonthIncomeUsd,
            'last_month_usd' => $lastMonthIncomeUsd,
            'growth_percent' => $growth,
            'by_category' => $byCategory,
            'by_method' => $byMethod,
            'total_count' => \App\Models\Income::whereBetween('income_date', [$startDate, $endDate])
                ->where('status', \App\Enums\IncomeStatus::Received)
                ->count(),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            IncomeStatsWidget::class,
        ];
    }
}

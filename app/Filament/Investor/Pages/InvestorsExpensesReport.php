<?php

namespace App\Filament\Investor\Pages;

use App\Exports\InvestorExpenseSummaryExport;
use App\Service\InvestorCompanyPerformanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class InvestorsExpensesReport extends Page
{
    protected static string $view = 'filament.investor.pages.investors-expenses-report';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

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

    public function getSummary(): array
    {
        return app(InvestorCompanyPerformanceService::class)->expenseSummary(
            Carbon::parse($this->start_date ?: now()->startOfMonth()),
            Carbon::parse($this->end_date ?: now())
        );
    }

    public function downloadPdf()
    {
        $summary = $this->getSummary();

        $pdf = Pdf::loadView('reports.investor-expense-summary-pdf', ['summary' => $summary])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "expense-summary-{$summary['start_date']}-to-{$summary['end_date']}.pdf");
    }

    public function downloadExcel()
    {
        $summary = $this->getSummary();

        return Excel::download(
            new InvestorExpenseSummaryExport($summary),
            "expense-summary-{$summary['start_date']}-to-{$summary['end_date']}.xlsx"
        );
    }
}

<?php

namespace App\Filament\Pages\Concerns;

use App\Enums\FiscalPeriodSource;
use App\Models\FiscalPeriod;
use App\Service\FinancialStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Shared plumbing for the three bank-facing statement pages: a fiscal-year selector,
 * the statement payload, and a PDF download. Each page supplies its own blade,
 * filename and service call.
 */
trait RendersFinancialStatement
{
    public int $selectedYear;

    public function mount(): void
    {
        $this->selectedYear = (int) now()->format('Y');
    }

    /**
     * Years the bank is likely to ask for: everything from the earliest year the
     * business has accounts for through the current one, newest first.
     *
     * @return array<int, string>
     */
    public function yearOptions(): array
    {
        $earliest = min(
            FinancialStatementService::firstRecordedYear() - 3,
            (int) (FiscalPeriod::min('year') ?? FinancialStatementService::firstRecordedYear())
        );

        return collect(range((int) now()->format('Y'), $earliest))
            ->mapWithKeys(function (int $year) {
                $period = FiscalPeriod::where('year', $year)->first();
                $suffix = $period?->source === FiscalPeriodSource::manual ? ' (entered)' : '';

                return [$year => $year.$suffix];
            })
            ->all();
    }

    /**
     * The year selector each page places in its own form(). Deliberately not a
     * form() in this trait — that would collide with InteractsWithForms::form().
     */
    public function yearSelect(): Select
    {
        return Select::make('selectedYear')
            ->label('Financial Year')
            ->options($this->yearOptions())
            ->live()
            ->required();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action('downloadPdf'),

            Action::make('downloadExcel')
                ->label('Download Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action('downloadExcel'),
        ];
    }

    public function downloadPdf()
    {
        $statement = $this->statement();

        $pdf = Pdf::loadView($this->pdfView(), ['statement' => $statement])
            ->setPaper('a4', $this->pdfOrientation())
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $this->pdfFilename().'-'.$this->selectedYear.'.pdf'
        );
    }

    /**
     * Built from the same statement payload the PDF renders, so a bank asking for the
     * figures in a spreadsheet gets exactly what the PDF shows, line for line.
     */
    public function downloadExcel()
    {
        $export = $this->excelExport($this->statement());

        return Excel::download($export, $this->pdfFilename().'-'.$this->selectedYear.'.xlsx');
    }

    protected function service(): FinancialStatementService
    {
        return app(FinancialStatementService::class);
    }

    protected function pdfOrientation(): string
    {
        return 'portrait';
    }

    /** @return array<string, mixed> */
    abstract public function statement(): array;

    abstract protected function pdfView(): string;

    abstract protected function pdfFilename(): string;

    /** @param array<string, mixed> $statement */
    abstract protected function excelExport(array $statement): object;
}

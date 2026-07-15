<?php

namespace App\Filament\Pages;

use App\Exports\ClientReportExport;
use App\Service\ClientReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ClientReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.pages.client-reports';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Client Reports';

    public ?string $report_type = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?int $top_clients_limit = 20;

    public ?Collection $reportData = null;

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->toDateString();
        $this->end_date = now()->toDateString();
        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'top_clients_limit' => $this->top_clients_limit,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Report Parameters')
                    ->schema([
                        Select::make('report_type')
                            ->label('Report Type')
                            ->options([
                                'client_summary' => 'Client Summary (shipping & payment status per client)',
                                'status_breakdown' => 'Status Breakdown (company-wide statistics)',
                                'new_clients' => 'New Clients in Period',
                                'top_clients' => 'Top Clients by Revenue',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function () {
                                $this->reportData = null;
                            }),

                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required(),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->required(),

                        TextInput::make('top_clients_limit')
                            ->label('Number of Clients')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(200)
                            ->visible(fn ($get) => $get('report_type') === 'top_clients'),
                    ])
                    ->columns(4),
            ]);
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();
        $service = app(ClientReportService::class);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        $this->reportData = match ($data['report_type']) {
            'client_summary' => $service->clientSummary($start, $end),
            'status_breakdown' => collect([$service->statusBreakdown($start, $end)]),
            'new_clients' => $service->newClients($start, $end),
            'top_clients' => $service->topClients($start, $end, (int) ($data['top_clients_limit'] ?? 20)),
            default => collect(),
        };
    }

    protected function reportTitle(string $reportType): string
    {
        return match ($reportType) {
            'client_summary' => 'Client Summary Report',
            'status_breakdown' => 'Client Status Breakdown Report',
            'new_clients' => 'New Clients Report',
            'top_clients' => 'Top Clients by Revenue Report',
            default => 'Client Report',
        };
    }

    public function exportPdf()
    {
        if (! $this->reportData || $this->reportData->count() === 0) {
            Notification::make()->warning()->title('No data to export')->body('Please generate a report first.')->send();

            return;
        }

        $data = $this->form->getState();
        $reportType = $data['report_type'];
        $title = $this->reportTitle($reportType);

        $pdf = Pdf::loadView('reports.client-report-pdf', [
            'data' => $this->reportData,
            'reportType' => $reportType,
            'title' => $title,
            'startDate' => $data['start_date'] ?? null,
            'endDate' => $data['end_date'] ?? null,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        $filename = str_replace(' ', '_', strtolower($title)).'_'.now()->format('Y-m-d').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    public function exportExcel()
    {
        if (! $this->reportData || $this->reportData->count() === 0) {
            Notification::make()->warning()->title('No data to export')->body('Please generate a report first.')->send();

            return;
        }

        $data = $this->form->getState();
        $reportType = $data['report_type'];
        $filename = str_replace(' ', '_', strtolower($this->reportTitle($reportType))).'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new ClientReportExport($this->reportData, $reportType), $filename);
    }
}

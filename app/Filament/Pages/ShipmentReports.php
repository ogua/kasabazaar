<?php

namespace App\Filament\Pages;

use App\Exports\ShipmentReportExport;
use App\Models\Shipment;
use App\Service\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ShipmentReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string $view = 'filament.pages.shipment-reports';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Shipment Reports';

    public ?string $report_type = null;

    public ?int $year = null;

    public ?int $container_sequence = null;

    public ?string $client_id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?Collection $reportData = null;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->form->fill([
            'year' => $this->year,
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
                                'by_container' => 'Shipments by Container',
                                'by_year' => 'Shipments by Year',
                                'by_date_range' => 'Shipments by Date Range',
                                'profit_loss' => 'Profit/Loss by Container',
                                'client_shipments' => 'Client Shipment History',
                                'debtors' => 'Debtors Report (Unpaid by Container)',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->reportData = null;
                            }),

                        Select::make('client_id')
                            ->label('Client')
                            ->searchable()
                            ->options(function () {
                                return \App\Models\Client::orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->visible(fn ($get) => $get('report_type') === 'client_shipments')
                            ->required(fn ($get) => $get('report_type') === 'client_shipments'),

                        Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = [];
                                for ($y = now()->year; $y >= 2020; $y--) {
                                    $years[$y] = $y;
                                }

                                return $years;
                            })
                            ->visible(fn ($get) => in_array($get('report_type'), ['by_container', 'by_year', 'profit_loss', 'debtors']))
                            ->required(),

                        Select::make('container_sequence')
                            ->label('Container Sequence')
                            ->options(function ($get) {
                                if (! $get('year')) {
                                    return [];
                                }
                                $yearShort = substr((string) $get('year'), -2);

                                return Shipment::where('shipping_reference', 'like', "%-{$yearShort}-%")
                                    ->distinct()
                                    ->pluck('container_number', 'container_number')
                                    ->mapWithKeys(fn ($val) => [$val => "CON{$val}"])
                                    ->toArray();
                            })
                            ->visible(fn ($get) => in_array($get('report_type'), ['by_container', 'profit_loss', 'debtors'])),

                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->visible(fn ($get) => in_array($get('report_type'), ['client_shipments', 'by_date_range']))
                            ->required(fn ($get) => $get('report_type') === 'by_date_range'),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->visible(fn ($get) => in_array($get('report_type'), ['client_shipments', 'by_date_range']))
                            ->required(fn ($get) => $get('report_type') === 'by_date_range'),
                    ])
                    ->columns(4),
            ]);
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();
        $reportService = app(ReportService::class);

        $this->reportData = match ($data['report_type']) {
            'by_container' => $reportService->shipmentsByContainer(
                $data['year'],
                $data['container_sequence'] ?? null
            ),
            'by_year' => $reportService->shipmentsByYear($data['year']),
            'by_date_range' => $reportService->shipmentsByDateRange(
                $data['start_date'] ?? null,
                $data['end_date'] ?? null
            ),
            'profit_loss' => $reportService->shipmentsByContainerSequence(
                $data['year'],
                $data['container_sequence'] ?? null
            ),
            'client_shipments' => $reportService->clientShipmentHistory(
                $data['client_id'],
                $data['start_date'] ?? null,
                $data['end_date'] ?? null
            ),
            'debtors' => $reportService->debtorsByContainer(
                $data['year'],
                $data['container_sequence'] ?? null
            ),
            default => collect(),
        };
    }

    public function exportPdf()
    {
        if (! $this->reportData || $this->reportData->count() === 0) {
            Notification::make()
                ->warning()
                ->title('No data to export')
                ->body('Please generate a report first.')
                ->send();

            return;
        }

        $data = $this->form->getState();
        $reportType = $data['report_type'];

        $title = match ($reportType) {
            'by_container' => 'Shipments by Container Report',
            'by_year' => 'Shipments by Year Report',
            'by_date_range' => 'Shipments by Date Range Report',
            'profit_loss' => 'Profit/Loss Report',
            'client_shipments' => 'Client Shipment History Report',
            'debtors' => 'Debtors Report',
            default => 'Shipment Report'
        };

        $pdf = Pdf::loadView('reports.shipment-report-pdf', [
            'data' => $this->reportData,
            'reportType' => $reportType,
            'title' => $title,
            'year' => $data['year'] ?? null,
            'containerSequence' => $data['container_sequence'] ?? null,
            'startDate' => $data['start_date'] ?? null,
            'endDate' => $data['end_date'] ?? null,
        ]);

        $filename = str_replace(' ', '_', strtolower($title)).'_'.now()->format('Y-m-d').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    public function exportExcel()
    {
        if (! $this->reportData || $this->reportData->count() === 0) {
            Notification::make()
                ->warning()
                ->title('No data to export')
                ->body('Please generate a report first.')
                ->send();

            return;
        }

        $data = $this->form->getState();
        $reportType = $data['report_type'];

        $title = match ($reportType) {
            'by_container' => 'Shipments_by_Container',
            'by_year' => 'Shipments_by_Year',
            'by_date_range' => 'Shipments_by_Date_Range',
            'profit_loss' => 'Profit_Loss_Report',
            'client_shipments' => 'Client_Shipment_History',
            'debtors' => 'Debtors_Report',
            default => 'Shipment_Report'
        };

        $filename = $title.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new ShipmentReportExport($this->reportData, $reportType),
            $filename
        );
    }

    public function exportWord()
    {
        if (! $this->reportData || $this->reportData->count() === 0) {
            Notification::make()
                ->warning()
                ->title('No data to export')
                ->body('Please generate a report first.')
                ->send();

            return;
        }

        $data = $this->form->getState();
        $reportType = $data['report_type'];

        $title = match ($reportType) {
            'by_container' => 'Shipments by Container Report',
            'by_year' => 'Shipments by Year Report',
            'by_date_range' => 'Shipments by Date Range Report',
            'profit_loss' => 'Profit/Loss Report',
            'client_shipments' => 'Client Shipment History Report',
            'debtors' => 'Debtors Report',
            default => 'Shipment Report'
        };

        // Generate HTML content
        $html = view('reports.shipment-report-pdf', [
            'data' => $this->reportData,
            'reportType' => $reportType,
            'title' => $title,
            'year' => $data['year'] ?? null,
            'containerSequence' => $data['container_sequence'] ?? null,
            'startDate' => $data['start_date'] ?? null,
            'endDate' => $data['end_date'] ?? null,
        ])->render();

        // Create Word document structure
        $wordContent = '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <title>'.$title.'</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
</head>
<body>'.$html.'</body>
</html>';

        $filename = str_replace(' ', '_', strtolower($title)).'_'.now()->format('Y-m-d').'.doc';

        return response()->streamDownload(function () use ($wordContent) {
            echo $wordContent;
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-word',
            'Content-Disposition' => 'attachment;filename="'.$filename.'"',
        ]);
    }
}

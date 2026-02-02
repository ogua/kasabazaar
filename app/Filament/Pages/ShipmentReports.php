<?php

namespace App\Filament\Pages;

use App\Models\Shipment;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Service\ReportService;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;

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
                                'profit_loss' => 'Profit/Loss by Container',
                                'client_shipments' => 'Client Shipment History',
                            ])
                            ->required()
                            ->live(),

                        Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = [];
                                for ($y = now()->year; $y >= 2020; $y--) {
                                    $years[$y] = $y;
                                }
                                return $years;
                            })
                            ->visible(fn ($get) => in_array($get('report_type'), ['by_container', 'by_year', 'profit_loss']))
                            ->required(),

                        Select::make('container_sequence')
                            ->label('Container Sequence')
                            ->options(function ($get) {
                                if (!$get('year')) return [];
                                $yearShort = substr((string) $get('year'), -2);
                                return Shipment::where('shipping_reference', 'like', "%-{$yearShort}-%")
                                    ->distinct()
                                    ->pluck('client_sequence', 'client_sequence')
                                    ->mapWithKeys(fn ($val) => [$val => "C{$val}"])
                                    ->toArray();
                            })
                            ->visible(fn ($get) => in_array($get('report_type'), ['by_container', 'profit_loss'])),

                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->visible(fn ($get) => $get('report_type') === 'client_shipments'),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->visible(fn ($get) => $get('report_type') === 'client_shipments'),
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
            'profit_loss' => $reportService->shipmentsByContainerSequence(
                $data['year'],
                $data['container_sequence'] ?? null
            ),
            default => collect(),
        };
    }
}

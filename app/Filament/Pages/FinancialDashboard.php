<?php

namespace App\Filament\Pages;

use App\Models\Shipment;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Widgets\TopStatesWidget;
use Filament\Forms\Components\DatePicker;
use App\Filament\Widgets\IncomeStatsWidget;
use App\Filament\Widgets\ExpenseStatsWidget;
use App\Filament\Widgets\PayrollStatsWidget;
use App\Filament\Widgets\ManagementKPIWidget;
use App\Filament\Widgets\ContainerProfitWidget;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Widgets\ExpensesByCategoryChart;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\MonthlyExpenseIncomeChart;

class FinancialDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.pages.financial-dashboard';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Financial Dashboard';

    protected static ?string $navigationLabel = 'Financial Dashboard';

    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $container_number = null;

    public static function getNavigationBadge(): ?string
    {
        return 'Management';
    }

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
        $this->container_number = null;

        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'container_number' => $this->container_number,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dashboard Filters')
                    ->description('Filter dashboard metrics by date range and specific shipment')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(now()->startOfMonth())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateDashboard()),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->default(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateDashboard()),

                        Select::make('container_number')
                            ->label('Container (Optional)')
                            ->placeholder('All Containers')
                            ->searchable()
                            ->options(function () {
                                return Shipment::whereNotNull('container_number')
                                    ->select('container_number')
                                    ->selectRaw('COUNT(*) as shipment_count')
                                    ->selectRaw('SUM(total) as total_amount')
                                    ->selectRaw('MIN(created_at) as first_shipment')
                                    ->groupBy('container_number')
                                    ->orderBy('first_shipment', 'desc')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($container) {
                                        $label = 'CON' . $container->container_number .
                                                ' (' . $container->shipment_count . ' shipments, ' .
                                                '$' . number_format($container->total_amount, 2) . ')';
                                        return [$container->container_number => $label];
                                    })
                                    ->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateDashboard()),
                    ])
                    ->columns(3)
                    ->compact(),
            ])
            ->statePath('data');
    }

    public function updateDashboard(): void
    {
        $data = $this->form->getState();
        $this->start_date = $data['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $this->end_date = $data['end_date'] ?? now()->format('Y-m-d');
        $this->container_number = $data['container_number'] ?? null;

        // Dispatch event to refresh widgets
        $this->dispatch('filtersUpdated', [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'container_number' => $this->container_number,
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        //return [];
        return [
            FinancialOverviewWidget::class,
            ManagementKPIWidget::class,
            ContainerProfitWidget::class,
            TopStatesWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ExpenseStatsWidget::class,
            IncomeStatsWidget::class,
            PayrollStatsWidget::class,
            ExpensesByCategoryChart::class,
            MonthlyExpenseIncomeChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1; // Full width for comprehensive widgets
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 3; // 3 columns for footer stats
    }
}

<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\ManagementKPIWidget;
use App\Models\Shipment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class Dashboard extends BaseDashboard implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.dashboard';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?string $container_number = null;

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
                    ->description('Filter all dashboard metrics by date range and container')
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
                                        $label = 'CON'.$container->container_number.
                                                ' ('.$container->shipment_count.' shipments, '.
                                                '$'.number_format($container->total_amount, 2).')';

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

    /**
     * Stat widgets whose cards draw trend graphs; they sit with the charts.
     *
     * @var array<int, class-string<Widget>>
     */
    protected const GRAPH_STAT_WIDGETS = [
        ManagementKPIWidget::class,
    ];

    /**
     * The financial overview first, then every chart (and the stat cards that draw
     * trend graphs), then the remaining stats and tables. Each group keeps its
     * widgets' own sort order, so a new chart lands with the charts by its type.
     *
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        $widgets = collect(parent::getWidgets());
        $classOf = fn ($widget): string => $widget instanceof WidgetConfiguration ? $widget->widget : $widget;

        $financial = $widgets->filter(fn ($widget): bool => $classOf($widget) === FinancialOverviewWidget::class);
        $charts = $widgets->filter(fn ($widget): bool => is_subclass_of($classOf($widget), ChartWidget::class))
            ->union($widgets->filter(fn ($widget): bool => in_array($classOf($widget), self::GRAPH_STAT_WIDGETS, true)));

        return $financial
            ->merge($charts)
            ->merge($widgets->except($financial->keys()->merge($charts->keys())))
            ->values()
            ->all();
    }

    public function updateDashboard(): void
    {
        $data = $this->form->getState();
        $this->start_date = $data['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $this->end_date = $data['end_date'] ?? now()->format('Y-m-d');
        $this->container_number = $data['container_number'] ?? null;

        // Dispatch event to refresh widgets
        $this->dispatch('dashboardFiltersUpdated', [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'container_number' => $this->container_number,
        ]);
    }
}

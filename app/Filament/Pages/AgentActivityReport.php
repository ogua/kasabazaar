<?php

namespace App\Filament\Pages;

use App\Exports\AgentActivityExport;
use App\Models\ClearingAgent;
use App\Models\ContainerClearance;
use App\Models\ShipmentDelivery;
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

class AgentActivityReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string $view = 'filament.pages.agent-activity-report';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Agent Activity Report';

    public ?string $date = null;

    public ?string $clearing_agent_id = null;

    public ?Collection $clearances = null;

    public ?Collection $deliveries = null;

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->form->fill([
            'date' => $this->date,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filters')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Date')
                            ->default(now())
                            ->required(),

                        Select::make('clearing_agent_id')
                            ->label('Agent')
                            ->placeholder('All agents')
                            ->options(fn () => ClearingAgent::query()->pluck('name', 'id'))
                            ->searchable(),
                    ])
                    ->columns(2),
            ]);
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();
        $date = $data['date'];
        $agentId = $data['clearing_agent_id'] ?? null;

        $this->clearances = ContainerClearance::query()
            ->whereDate('cleared_at', $date)
            ->when($agentId, fn ($query) => $query->where('clearing_agent_id', $agentId))
            ->with(['clearingAgent', 'recordedBy'])
            ->orderBy('cleared_at')
            ->get();

        $this->deliveries = ShipmentDelivery::query()
            ->whereDate('delivered_at', $date)
            ->when($agentId, fn ($query) => $query->where('clearing_agent_id', $agentId))
            ->with(['clearingAgent', 'shipment', 'recordedBy'])
            ->orderBy('delivered_at')
            ->get();
    }

    public function exportExcel()
    {
        if ((! $this->clearances || $this->clearances->isEmpty()) && (! $this->deliveries || $this->deliveries->isEmpty())) {
            Notification::make()
                ->warning()
                ->title('No data to export')
                ->body('Please generate a report first.')
                ->send();

            return;
        }

        $filename = 'agent_activity_'.($this->date ?? now()->toDateString()).'.xlsx';

        return Excel::download(
            new AgentActivityExport($this->clearances ?? collect(), $this->deliveries ?? collect()),
            $filename
        );
    }
}

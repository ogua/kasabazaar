<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use App\Exports\AccountsReceivableExport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Pages\Concerns\RendersFinancialStatement;

class AccountsReceivableSchedule extends Page implements HasForms
{
    use InteractsWithForms, RendersFinancialStatement;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Accounts Receivable';

    protected static ?string $navigationLabel = 'Accounts Receivable';

    protected static string $view = 'filament.pages.accounts-receivable-schedule';

    /** Ageing is always stated as at an explicit date, never an implicit "today". */
    public ?string $asOf = null;

    public static function getNavigationBadge(): ?string
    {
        return 'New';
    }

    public function mount(): void
    {
        $this->selectedYear = (int) now()->format('Y');
        $this->asOf = now()->toDateString();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->yearSelect()
                    ->afterStateUpdated(function () {
                        // Snap to the year end when a closed year is chosen, since that
                        // is the date a bank asks the schedule to be stated at.
                        $this->asOf = $this->selectedYear < (int) now()->format('Y')
                            ? Carbon::create($this->selectedYear, 12, 31)->toDateString()
                            : now()->toDateString();
                    }),

                DatePicker::make('asOf')
                    ->label('As at')
                    ->live()
                    ->required(),
            ])
            ->columns(2);
    }

    public function statement(): array
    {
        return $this->service()->accountsReceivable(
            $this->selectedYear,
            $this->asOf ? Carbon::parse($this->asOf) : null
        );
    }

    protected function pdfView(): string
    {
        return 'reports.accounts-receivable-pdf';
    }

    protected function excelExport(array $statement): object
    {
        return new AccountsReceivableExport($statement);
    }

    protected function pdfFilename(): string
    {
        return 'accounts-receivable';
    }

    protected function pdfOrientation(): string
    {
        return 'landscape';
    }
}

<?php

namespace App\Filament\Pages;

use App\Enums\CashbookCostCenter;
use App\Models\CashbookEntry;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MonthlyCashbook extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Cashbook';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Monthly Cashbook';
    protected static ?string $navigationLabel = 'Monthly Cashbook';
    protected static string $view = 'filament.pages.monthly-cashbook';

    public int $selectedMonth;
    public int $selectedYear;

    public function mount(): void
    {
        $this->selectedMonth = (int) now()->format('n');
        $this->selectedYear  = (int) now()->format('Y');
    }

    public function updatedSelectedMonth(): void
    {
        $this->resetTable();
    }

    public function updatedSelectedYear(): void
    {
        $this->resetTable();
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => CashbookEntry::query()
                ->forMonth($this->selectedMonth, $this->selectedYear)
                ->orderBy('date')
                ->orderBy('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pv_no')
                    ->label('PV No')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('details')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('chq_ref')
                    ->label('CHQ')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('bank_debit')
                    ->label('Bank Dr')
                    ->numeric(2)
                    ->prefix('₵')
                    ->color(fn ($state) => $state > 0 ? 'success' : null),
                Tables\Columns\TextColumn::make('momo_debit')
                    ->label('MOMO Dr')
                    ->numeric(2)
                    ->prefix('₵')
                    ->color(fn ($state) => $state > 0 ? 'success' : null),
                Tables\Columns\TextColumn::make('bank_credit')
                    ->label('Bank Cr')
                    ->numeric(2)
                    ->prefix('₵')
                    ->color(fn ($state) => $state > 0 ? 'danger' : null),
                Tables\Columns\TextColumn::make('momo_credit')
                    ->label('MOMO Cr')
                    ->numeric(2)
                    ->prefix('₵')
                    ->color(fn ($state) => $state > 0 ? 'danger' : null),
                Tables\Columns\TextColumn::make('bank_balance')
                    ->label('Bank Bal')
                    ->numeric(2)
                    ->prefix('₵')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('momo_balance')
                    ->label('MOMO Bal')
                    ->numeric(2)
                    ->prefix('₵')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('cost_center')
                    ->label('Cost Center')
                    ->badge()
                    ->formatStateUsing(fn (CashbookCostCenter $state) => $state->getLabel())
                    ->color(fn (CashbookCostCenter $state) => $state->getColor()),
            ])
            ->defaultSort('date', 'asc')
            ->striped()
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form($this->entryFormSchema())
                    ->using(function (CashbookEntry $record, array $data): CashbookEntry {
                        $data['month'] = $this->selectedMonth;
                        $data['year']  = $this->selectedYear;
                        $record->update($data);
                        return $record;
                    })
                    ->successNotificationTitle('Entry updated'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ─── Header Actions ───────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newEntry')
                ->label('New Entry')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form($this->entryFormSchema())
                ->action(function (array $data): void {
                    $data['month'] = $this->selectedMonth;
                    $data['year']  = $this->selectedYear;
                    CashbookEntry::create($data);
                    Notification::make()
                        ->title('Entry added')
                        ->success()
                        ->send();
                    $this->resetTable();
                }),
        ];
    }

    // ─── Shared form schema ───────────────────────────────────────────────────

    protected function entryFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Transaction Details')
                ->schema([
                    Forms\Components\DatePicker::make('date')
                        ->required()
                        ->default(now()->startOfMonth()),
                    Forms\Components\TextInput::make('pv_no')
                        ->label('PV No')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('details')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('chq_ref')
                        ->label('CHQ / Payment Ref')
                        ->placeholder('BL / Momo / Chq 000005')
                        ->maxLength(50),
                    Forms\Components\Select::make('cost_center')
                        ->label('Cost Center')
                        ->options(CashbookCostCenter::class)
                        ->required()
                        ->searchable()
                        ->reactive(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Amounts (leave unused at 0)')
                ->schema([
                    Forms\Components\TextInput::make('bank_debit')
                        ->label('Bank Debit (received)')
                        ->numeric()
                        ->prefix('₵')
                        ->default(0)
                        ->minValue(0),
                    Forms\Components\TextInput::make('momo_debit')
                        ->label('MOMO Debit (received)')
                        ->numeric()
                        ->prefix('₵')
                        ->default(0)
                        ->minValue(0),
                    Forms\Components\TextInput::make('bank_credit')
                        ->label('Bank Credit (paid out)')
                        ->numeric()
                        ->prefix('₵')
                        ->default(0)
                        ->minValue(0),
                    Forms\Components\TextInput::make('momo_credit')
                        ->label('MOMO Credit (paid out)')
                        ->numeric()
                        ->prefix('₵')
                        ->default(0)
                        ->minValue(0),
                ])
                ->columns(2)
                ->description('Bank & MOMO balances are computed automatically. Analysis columns are filled by cost center.'),
        ];
    }

    // ─── Monthly totals for the view ──────────────────────────────────────────

    public function getMonthlyTotals(): array
    {
        return CashbookEntry::monthlyTotals($this->selectedMonth, $this->selectedYear);
    }

    public function getClosingBalances(): array
    {
        $last = CashbookEntry::query()
            ->forMonth($this->selectedMonth, $this->selectedYear)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->first();

        return [
            'bank' => $last?->bank_balance ?? 0,
            'momo' => $last?->momo_balance ?? 0,
        ];
    }

    public static function getMonthOptions(): array
    {
        return [
            1 => 'January', 2 => 'February', 3 => 'March',
            4 => 'April',   5 => 'May',      6 => 'June',
            7 => 'July',    8 => 'August',   9 => 'September',
            10 => 'October', 11 => 'November', 12 => 'December',
        ];
    }

    public static function getYearOptions(): array
    {
        $currentYear = (int) now()->format('Y');
        return array_combine(
            range($currentYear - 1, $currentYear + 1),
            range($currentYear - 1, $currentYear + 1)
        );
    }
}

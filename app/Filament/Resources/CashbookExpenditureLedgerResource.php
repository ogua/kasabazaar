<?php

namespace App\Filament\Resources;

use App\Enums\ExpenditureLedgerType;
use App\Filament\Resources\CashbookExpenditureLedgerResource\Pages;
use App\Models\CashbookExpenditureLedger;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashbookExpenditureLedgerResource extends Resource
{
    protected static ?string $model = CashbookExpenditureLedger::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static ?string $navigationGroup = 'Cashbook';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Expenditure Ledger';

    protected static ?string $modelLabel = 'Expenditure Ledger Entry';

    protected static ?string $pluralModelLabel = 'Expenditure Ledger';

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ledger Entry')
                    ->schema([
                        Forms\Components\Select::make('month')
                            ->options([
                                1 => 'January', 2 => 'February', 3 => 'March',
                                4 => 'April',   5 => 'May',      6 => 'June',
                                7 => 'July',    8 => 'August',   9 => 'September',
                                10 => 'October', 11 => 'November', 12 => 'December',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('year')->numeric()->required(),
                        Forms\Components\Select::make('ledger_type')
                            ->label('Ledger Type')
                            ->options(ExpenditureLedgerType::class)
                            ->required(),
                        Forms\Components\TextInput::make('particulars')->default('Cash Book Payment'),
                        Forms\Components\TextInput::make('op_balance')->label('Opening Balance')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('debit')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('credit')->numeric()->prefix('₵')->default(0),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('month')
                    ->formatStateUsing(fn ($state) => [
                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
                    ][$state] ?? $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')->sortable(),
                Tables\Columns\TextColumn::make('ledger_type')
                    ->label('Ledger')
                    ->badge()
                    ->formatStateUsing(fn (ExpenditureLedgerType $state) => $state->getLabel()),
                Tables\Columns\TextColumn::make('particulars'),
                Tables\Columns\TextColumn::make('op_balance')->label('Op. Bal')->numeric(2)->prefix('₵'),
                Tables\Columns\TextColumn::make('debit')->numeric(2)->prefix('₵')->color('danger'),
                Tables\Columns\TextColumn::make('credit')->numeric(2)->prefix('₵')->color('success'),
                Tables\Columns\TextColumn::make('cl_balance')->label('Cl. Bal')->numeric(2)->prefix('₵')->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ledger_type')
                    ->label('Ledger Type')
                    ->options(ExpenditureLedgerType::class),
                Tables\Filters\SelectFilter::make('year')
                    ->options(fn () => CashbookExpenditureLedger::query()
                        ->distinct()->orderByDesc('year')
                        ->pluck('year', 'year')->toArray()),
            ])
            ->defaultSort('year', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashbookExpenditureLedgers::route('/'),
            'create' => Pages\CreateCashbookExpenditureLedger::route('/create'),
            'edit' => Pages\EditCashbookExpenditureLedger::route('/{record}/edit'),
        ];
    }
}

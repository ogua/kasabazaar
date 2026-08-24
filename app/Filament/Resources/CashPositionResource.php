<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashPositionResource\Pages;
use App\Models\CashPosition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Where the bank balance comes from. Nothing else in the system knows it — the
 * cashbook is unused, and shipments record what was invoiced and received, not the
 * resulting balance. Without a position recorded here the balance sheet shows
 * receivables and no cash, and cannot be made to balance.
 */
class CashPositionResource extends Resource
{
    protected static ?string $model = CashPosition::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Cash Positions';

    protected static ?int $navigationSort = 8;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Cash Position')
                ->description('The balances on your bank and mobile-money statements at a given date. Record one whenever you want the balance sheet to reflect a fresh cash position — month end is a sensible rhythm.')
                ->schema([
                    Forms\Components\DatePicker::make('as_of_date')
                        ->label('As at')
                        ->default(now()->endOfMonth())
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('The statement date. One position per date.'),

                    Forms\Components\Select::make('currency')
                        ->options(['GHS' => 'Ghana Cedis (GHS)', 'USD' => 'US Dollars (USD)'])
                        ->default('GHS')
                        ->live()
                        ->required()
                        ->helperText('The currency the balances below are stated in.'),

                    Forms\Components\TextInput::make('bank_balance')
                        ->label('Bank Balance')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    Forms\Components\TextInput::make('momo_balance')
                        ->label('Mobile Money Balance')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    Forms\Components\TextInput::make('exchange_rate')
                        ->label('Rate at this date (GHS per USD 1.00)')
                        ->numeric()
                        ->step('0.0001')
                        ->visible(fn (Get $get) => $get('currency') === 'GHS')
                        ->required(fn (Get $get) => $get('currency') === 'GHS')
                        ->helperText('Statements present in USD. The rate is stored with the position so it converts at the rate that applied then, rather than being restated later.'),

                    Forms\Components\Textarea::make('notes')
                        ->placeholder('e.g. Reconciled against the GCB statement of 31 July.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('as_of_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('as_of_date')
                    ->label('As at')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bank_balance')
                    ->label('Bank')
                    ->formatStateUsing(fn ($state, CashPosition $record) => $record->currency.' '.number_format((float) $state, 2))
                    ->alignRight(),

                Tables\Columns\TextColumn::make('momo_balance')
                    ->label('Mobile Money')
                    ->formatStateUsing(fn ($state, CashPosition $record) => $record->currency.' '.number_format((float) $state, 2))
                    ->alignRight(),

                Tables\Columns\TextColumn::make('total_usd')
                    ->label('Total (USD)')
                    ->state(fn (CashPosition $record) => $record->totalInPresentationCurrency())
                    ->money('USD')
                    ->alignRight()
                    ->weight('bold')
                    ->tooltip('What the balance sheet reports for Cash & Bank.'),

                Tables\Columns\TextColumn::make('exchange_rate')
                    ->label('Rate')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('No cash position recorded')
            ->emptyStateDescription('Until one is recorded the balance sheet shows no cash, and will not balance.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashPositions::route('/'),
            'create' => Pages\CreateCashPosition::route('/create'),
            'edit' => Pages\EditCashPosition::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Investor\Resources;

use App\Filament\Investor\Resources\InvestmentInterestPayoutResource\Pages;
use App\Models\InvestmentInterestPayout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvestmentInterestPayoutResource extends Resource
{
    protected static ?string $model = InvestmentInterestPayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Interest Payouts';

    protected static ?string $pluralModelLabel = 'Interest Payouts';

    protected static ?string $modelLabel = 'Interest Payout';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('investor_id', auth()->user()->investor_id)
            ->orderBy('due_date', 'desc');
    }

    // Investors authorize by ownership (scoped query above), not by the Shield
    // permissions the shared model's policy requires for admin staff. This is
    // system-generated — investors never create or edit their own payout rows.
    public static function canViewAny(): bool
    {
        return filled(auth()->user()?->investor_id);
    }

    public static function canView($record): bool
    {
        return $record->investor_id === auth()->user()->investor_id;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('investment_id')
                    ->label('Investment')
                    ->relationship('investment', 'reference')
                    ->disabled(),

                Forms\Components\DatePicker::make('period_start')
                    ->disabled(),

                Forms\Components\DatePicker::make('period_end')
                    ->disabled(),

                Forms\Components\DatePicker::make('due_date')
                    ->disabled(),

                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('USD')
                    ->disabled(),

                Forms\Components\TextInput::make('amount_paid')
                    ->label('Paid')
                    ->numeric()
                    ->prefix('USD')
                    ->disabled(),

                Forms\Components\Select::make('status')
                    ->options([
                        'due' => 'Due',
                        'processing' => 'Processing',
                        'paid' => 'Paid',
                        'skipped' => 'Skipped',
                        'reversed' => 'Reversed',
                    ])
                    ->disabled(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('investment.reference')
                    ->label('Investment'),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn ($record) => $record->period_start->format('M d, Y').' – '.$record->period_end->format('M d, Y')),

                Tables\Columns\TextColumn::make('due_date')
                    ->date('M d, Y'),

                Tables\Columns\TextColumn::make('amount')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('due_date', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestmentInterestPayouts::route('/'),
            'view' => Pages\ViewInvestmentInterestPayout::route('/{record}'),
        ];
    }
}

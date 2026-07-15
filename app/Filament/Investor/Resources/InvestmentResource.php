<?php

namespace App\Filament\Investor\Resources;

use App\Filament\Investor\Resources\InvestmentResource\Pages;
use App\Filament\Investor\Resources\InvestmentResource\RelationManagers\TransactionsRelationManager;
use App\Models\Investment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

class InvestmentResource extends Resource
{
    protected static ?string $model = Investment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'My Investments';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('investor_id', auth()->user()->investor_id)
            ->orderBy('start_date', 'desc');
    }

    // Investors authorize by ownership (scoped query above), not by the Shield
    // permissions the shared Investment model's policy requires for admin staff.
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
        return filled(auth()->user()?->investor_id);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('investor_id', auth()->user()->investor_id)
            ->where('status', 'active')
            ->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('New Investment')
                    ->description('Choose an amount and a payment method. You\'ll be taken to a secure checkout to complete the deposit — your investment activates automatically once payment is confirmed.')
                    ->schema([
                        Forms\Components\TextInput::make('principal_amount')
                            ->label('Amount to Invest')
                            ->numeric()
                            ->prefix('USD')
                            ->minValue(0.01)
                            ->required(),

                        Forms\Components\Select::make('deposit_gateway')
                            ->label('Payment Method')
                            ->options([
                                'paystack' => 'Paystack (Mobile Money / Ghana Card / Bank)',
                                'stripe' => 'Stripe (Card, International)',
                            ])
                            ->default('paystack')
                            ->required(),
                    ])
                    ->columns(1)
                    ->visible(fn (?Investment $record) => $record === null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),

                Tables\Columns\TextColumn::make('principal_amount')
                    ->label('Principal')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Current Value')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('maturity_date')
                    ->label('Contract Due')
                    ->date('M d, Y')
                    ->color(fn (Investment $record) => $record->isContractDue() ? 'success' : 'gray')
                    ->tooltip(fn (Investment $record) => $record->isContractDue()
                        ? 'Your contract has matured — you may request a withdrawal.'
                        : 'Withdrawal requests open once your contract term ends.')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('downloadAgreement')
                    ->label('Agreement')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->visible(fn (Investment $record) => $record->status->value === 'active')
                    ->url(fn (Investment $record) => URL::temporarySignedRoute('investment-agreement', now()->addDay(), $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestments::route('/'),
            'create' => Pages\CreateInvestment::route('/create'),
            'view' => Pages\ViewInvestment::route('/{record}'),
        ];
    }
}

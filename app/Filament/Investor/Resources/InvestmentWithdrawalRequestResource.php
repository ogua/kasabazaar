<?php

namespace App\Filament\Investor\Resources;

use App\Filament\Investor\Resources\InvestmentWithdrawalRequestResource\Pages;
use App\Models\Investment;
use App\Models\InvestmentWithdrawalRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvestmentWithdrawalRequestResource extends Resource
{
    protected static ?string $model = InvestmentWithdrawalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Withdrawal Requests';

    protected static ?string $pluralModelLabel = 'Withdrawal Requests';

    protected static ?string $modelLabel = 'Withdrawal Request';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('investor_id', auth()->user()->investor_id)
            ->orderBy('created_at', 'desc');
    }

    // Investors authorize by ownership (scoped query above), not by the Shield
    // permissions the shared model's policy requires for admin staff.
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('investment_id')
                    ->label('Investment')
                    ->options(fn () => Investment::where('investor_id', auth()->user()->investor_id)
                        ->where('status', 'active')
                        ->where('capital_type', \App\Enums\InvestmentCapitalType::investment->value)
                        ->where('maturity_date', '<=', now())
                        ->pluck('reference', 'id'))
                    ->helperText('Only investment tranches whose contract term has elapsed are eligible for a withdrawal request. Loans are not eligible for early withdrawal — principal is due in full at maturity per your Loan Agreement.')
                    ->required()
                    ->disabled(fn (?InvestmentWithdrawalRequest $record) => $record !== null),

                Forms\Components\Toggle::make('is_full_withdrawal')
                    ->label('Withdraw Full Balance')
                    ->live()
                    ->disabled(fn (?InvestmentWithdrawalRequest $record) => $record !== null),

                Forms\Components\TextInput::make('requested_amount')
                    ->label('Amount to Withdraw')
                    ->numeric()
                    ->prefix('USD')
                    ->visible(fn (Get $get) => ! $get('is_full_withdrawal'))
                    ->required(fn (Get $get) => ! $get('is_full_withdrawal'))
                    ->disabled(fn (?InvestmentWithdrawalRequest $record) => $record !== null),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull()
                    ->disabled(fn (?InvestmentWithdrawalRequest $record) => $record !== null),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('investment.reference')
                    ->label('Investment'),

                Tables\Columns\IconColumn::make('is_full_withdrawal')
                    ->label('Full')
                    ->boolean(),

                Tables\Columns\TextColumn::make('requested_amount')
                    ->money('USD')
                    ->placeholder('Full balance'),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('required_action_by')
                    ->label('Notice Ends')
                    ->date('M d, Y'),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->money('USD'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestmentWithdrawalRequests::route('/'),
            'create' => Pages\CreateInvestmentWithdrawalRequest::route('/create'),
            'view' => Pages\ViewInvestmentWithdrawalRequest::route('/{record}'),
        ];
    }
}

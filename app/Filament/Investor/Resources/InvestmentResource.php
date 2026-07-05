<?php

namespace App\Filament\Investor\Resources;

use App\Filament\Investor\Resources\InvestmentResource\Pages;
use App\Filament\Investor\Resources\InvestmentResource\RelationManagers\TransactionsRelationManager;
use App\Models\Investment;
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

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('investor_id', auth()->user()->investor_id)
            ->where('status', 'active')
            ->count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('downloadAgreement')
                    ->label('Agreement')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
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
            'view' => Pages\ViewInvestment::route('/{record}'),
        ];
    }
}

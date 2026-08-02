<?php

namespace App\Filament\Resources\InvestmentResource\RelationManagers;

use App\Enums\InvestmentCapitalType;
use App\Models\Investment;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InterestPayoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'interestPayouts';

    protected static ?string $title = 'Interest Payouts';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Investment
            && $ownerRecord->capital_type === InvestmentCapitalType::loan;
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('due_date')
            ->columns([
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn ($record) => $record->period_start->format('M d, Y').' – '.$record->period_end->format('M d, Y')),

                Tables\Columns\TextColumn::make('due_date')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rate_applied')
                    ->label('Rate')
                    ->suffix('%'),

                Tables\Columns\TextColumn::make('amount')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('due_date', 'desc')
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => \App\Filament\Resources\InvestmentInterestPayoutResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}

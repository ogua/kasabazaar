<?php

namespace App\Filament\Investor\Resources\InvestmentResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Ledger';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge(),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn ($record) => $record->period_start && $record->period_end
                        ? $record->period_start->format('M d, Y').' – '.$record->period_end->format('M d, Y')
                        : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rate_applied')
                    ->label('Rate')
                    ->suffix('%')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('debit')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('credit')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('cl_balance')
                    ->label('Balance')
                    ->money('USD'),
            ])
            ->defaultSort('date', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->paginated([10, 25, 50]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}

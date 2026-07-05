<?php

namespace App\Filament\Resources\InvestmentResource\RelationManagers;

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

                Tables\Columns\TextColumn::make('debit')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('credit')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('cl_balance')
                    ->label('Balance')
                    ->money('USD'),

                Tables\Columns\IconColumn::make('posted')
                    ->boolean(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),
            ])
            ->defaultSort('date', 'desc')
            ->filters([])
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

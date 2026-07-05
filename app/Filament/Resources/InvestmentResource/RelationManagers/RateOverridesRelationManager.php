<?php

namespace App\Filament\Resources\InvestmentResource\RelationManagers;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RateOverridesRelationManager extends RelationManager
{
    protected static string $relationship = 'rateOverrides';

    protected static ?string $title = 'Rate Overrides';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('year')
                    ->numeric()
                    ->required()
                    ->minValue(2000)
                    ->maxValue(2100),

                Forms\Components\TextInput::make('annual_rate')
                    ->label('Annual Rate')
                    ->numeric()
                    ->suffix('%')
                    ->required(),

                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => Filament::auth()->id()),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('year')
            ->columns([
                Tables\Columns\TextColumn::make('year'),

                Tables\Columns\TextColumn::make('annual_rate')
                    ->label('Annual Rate')
                    ->suffix('%'),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Set By'),
            ])
            ->defaultSort('year', 'desc')
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

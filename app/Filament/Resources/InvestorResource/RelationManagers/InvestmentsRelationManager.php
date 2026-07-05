<?php

namespace App\Filament\Resources\InvestorResource\RelationManagers;

use App\Filament\Resources\InvestmentResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvestmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'investments';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                Tables\Columns\TextColumn::make('reference'),

                Tables\Columns\TextColumn::make('principal_amount')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('current_balance')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date('M d, Y'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('createInvestment')
                    ->label('New Investment')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => InvestmentResource::getUrl('create', ['investor_id' => $this->getOwnerRecord()->id])),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => InvestmentResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([])
            ->paginated(false);
    }
}

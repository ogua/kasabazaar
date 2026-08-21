<?php

namespace App\Filament\Resources\InvestorResource\RelationManagers;

use App\Filament\Resources\InvestmentConversionResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The investor's conversion history. Read-only here — a conversion is always raised
 * against specific tranches, so it is started from the "Convert Capital" action on an
 * investment or by the investor in their portal, never from a blank form.
 */
class ConversionsRelationManager extends RelationManager
{
    protected static string $relationship = 'conversions';

    protected static ?string $title = 'Capital Conversions';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable(),

                Tables\Columns\TextColumn::make('direction')->badge(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Carried Forward')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('sources_count')
                    ->label('Tranches')
                    ->counts('sources'),

                Tables\Columns\TextColumn::make('targetInvestment.reference')
                    ->label('Successor')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')->badge(),

                Tables\Columns\IconColumn::make('requested_by_investor')
                    ->label('Investor-raised')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('conversion_date')->date('M d, Y'),
            ])
            ->emptyStateHeading('No conversions yet')
            ->emptyStateDescription('Start one from the "Convert Capital" action on any of this investor\'s active tranches.')
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn ($record) => InvestmentConversionResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }
}

<?php

namespace App\Filament\Resources\InvestmentResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\InvestmentTransaction;
use Filament\Tables\Actions\EditAction;
use App\Enums\InvestmentTransactionType;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteAction;
use App\Service\InvestmentInterestService;
use Filament\Resources\RelationManagers\RelationManager;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Ledger';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')
                ->required()
                ->default(now()),

                Forms\Components\DatePicker::make('period_start')
                    ->required()
                    ->default(now()),

                Forms\Components\TextInput::make('rate_applied')
                    ->label('Rate Applied')
                    ->numeric()
                    ->suffix('%')
                    ->required()
        ]);
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

                Tables\Columns\IconColumn::make('posted')
                    ->boolean(),

                Tables\Columns\TextColumn::make('edited_at')
                    ->label('Revised')
                    ->dateTime('M d, Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('description')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),
            ])
            ->defaultSort('date', 'desc')
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('reviseAmount')
                    ->label('Revise Amount')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (InvestmentTransaction $record) => $record->type === InvestmentTransactionType::interest_credit
                        && $record->posted
                        && (auth()->user()?->hasRole('super_admin') ?? false))
                    ->form([
                        Forms\Components\TextInput::make('new_amount')
                            ->label('New Interest Amount')
                            ->numeric()
                            ->prefix('USD')
                            ->required()
                            ->default(fn (InvestmentTransaction $record) => $record->credit),

                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Revise posted interest')
                    ->modalDescription('This recalculates every later transaction and the investment\'s current value. This cannot be automatically undone.')
                    ->action(function (InvestmentTransaction $record, array $data) {
                        try {
                            app(InvestmentInterestService::class)->reviseInterestTransaction(
                                $record,
                                (float) $data['new_amount'],
                                auth()->user(),
                                $data['reason']
                            );

                            Notification::make()
                                ->title('Interest revised, balances recalculated')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Could not revise')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                    DeleteAction::make(),
                    EditAction::make(),
            ])
            ->bulkActions([])
            ->paginated([10, 25, 50]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}

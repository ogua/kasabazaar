<?php

namespace App\Filament\Pages;

use App\Enums\InvestmentTransactionType;
use App\Models\InvestmentTransaction;
use App\Service\InvestmentInterestService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PendingInterestPostings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Investors';

    protected static ?string $navigationLabel = 'Pending Interest Postings';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.pending-interest-postings';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => InvestmentTransaction::query()
                ->where('type', InvestmentTransactionType::interest_credit->value)
                ->where('posted', false))
            ->columns([
                Tables\Columns\TextColumn::make('investment.reference')
                    ->label('Investment'),

                Tables\Columns\TextColumn::make('investor.name')
                    ->label('Investor'),

                Tables\Columns\TextColumn::make('year'),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->date('M d, Y')
                    ->formatStateUsing(fn ($record) => $record->period_start && $record->period_end
                        ? $record->period_start->format('M d, Y').' – '.$record->period_end->format('M d, Y')
                        : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rate_applied')
                    ->label('Rate')
                    ->suffix('%')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('op_balance')
                    ->label('Opening Balance')
                    ->money('USD'),

                Tables\Columns\TextInputColumn::make('credit')
                    ->label('Computed Interest')
                    ->type('number')
                    ->rules(['required', 'numeric', 'min:0']),

                Tables\Columns\TextColumn::make('description')
                    ->label('Basis')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
            ])
            ->defaultSort('year')
            ->actions([
                Tables\Actions\Action::make('post')
                    ->label('Approve & Post')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (InvestmentTransaction $record) {
                        app(InvestmentInterestService::class)->postDraft($record, auth()->user());

                        $period = $record->period_start && $record->period_end
                            ? $record->period_start->format('M d, Y').' – '.$record->period_end->format('M d, Y')
                            : $record->year;

                        Notification::make()
                            ->title("Posted interest for {$period}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Discard Draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulkPost')
                    ->label('Approve & Post Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records) {
                        $service = app(InvestmentInterestService::class);
                        $count = 0;

                        foreach ($records as $record) {
                            $service->postDraft($record, auth()->user());
                            $count++;
                        }

                        Notification::make()
                            ->title("Posted interest for {$count} investment(s)")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Discard Selected Drafts'),
                ]),
            ]);
    }
}

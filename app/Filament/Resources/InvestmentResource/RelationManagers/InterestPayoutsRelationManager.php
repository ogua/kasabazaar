<?php

namespace App\Filament\Resources\InvestmentResource\RelationManagers;

use App\Enums\InvestmentCapitalType;
use App\Enums\PaymentMethod;
use App\Models\Investment;
use App\Service\InvestmentInterestPayoutService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
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
            ->headerActions([
                $this->recordHistoricalPayoutAction(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => \App\Filament\Resources\InvestmentInterestPayoutResource::getUrl('view', ['record' => $record])),

                $this->revertToDueAction(),
            ])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    /**
     * Payouts are otherwise only ever created by the daily
     * app:generate-investment-interest-payout-drafts cron, driven off
     * next_payout_due_date. This lets staff backfill a period that was actually paid
     * to the lender before the loan's payout schedule was correctly configured (e.g.
     * cash paid outside the app, or paid via the wrong action before this pipeline
     * existed for this record) — restricted to already-elapsed periods that don't yet
     * have a payout row, computed from the same projectSchedule() the agreement PDF
     * uses, so the backfilled amount/dates always match what the lender was shown.
     */
    protected function recordHistoricalPayoutAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('recordHistoricalPayout')
            ->label('Record Historical Payout')
            ->icon('heroicon-o-clock')
            ->color('warning')
            ->form(function () {
                $investment = $this->getOwnerRecord();
                $schedule = app(InvestmentInterestPayoutService::class)->projectSchedule($investment);
                $existingDueDates = $investment->interestPayouts()
                    ->pluck('due_date')
                    ->map(fn ($date) => $date->toDateString())
                    ->all();

                $options = [];
                foreach ($schedule as $i => $row) {
                    if ($row['due_date']->isFuture() || in_array($row['due_date']->toDateString(), $existingDueDates, true)) {
                        continue;
                    }

                    $options[$i] = sprintf(
                        '%s – %s (due %s): $%s',
                        $row['period_start']->format('M j, Y'),
                        $row['period_end']->format('M j, Y'),
                        $row['due_date']->format('M j, Y'),
                        number_format($row['amount'], 2)
                    );
                }

                return [
                    Forms\Components\Select::make('period_index')
                        ->label('Period')
                        ->options($options)
                        ->required()
                        ->helperText('Only elapsed periods without an existing payout record are listed — amount is computed from the same schedule shown in the loan agreement.'),

                    Forms\Components\Toggle::make('mark_as_paid')
                        ->label('Cash already paid to the lender')
                        ->helperText('Off = interest has accrued/earned for this period but the cash payout itself hasn\'t happened yet (e.g. this loan only disburses at contract maturity) — the period is recorded as Due, payable later via the normal Record Payment action.')
                        ->default(false)
                        ->live(),

                    Forms\Components\Select::make('payout_gateway')
                        ->label('Payout Method')
                        ->options([
                            'manual' => 'Manual (bank transfer / cheque executed outside the app)',
                            'paystack' => 'Paystack Transfer',
                            'stripe' => 'Stripe',
                        ])
                        ->default('manual')
                        ->live()
                        ->visible(fn (Get $get) => $get('mark_as_paid'))
                        ->required(fn (Get $get) => $get('mark_as_paid')),

                    Forms\Components\Select::make('payment_method')
                        ->options(PaymentMethod::class)
                        ->visible(fn (Get $get) => $get('mark_as_paid') && $get('payout_gateway') === 'manual')
                        ->required(fn (Get $get) => $get('mark_as_paid') && $get('payout_gateway') === 'manual'),

                    Forms\Components\TextInput::make('payment_reference')
                        ->label('Payment Reference')
                        ->maxLength(255)
                        ->visible(fn (Get $get) => $get('mark_as_paid')),

                    Forms\Components\FileUpload::make('receipt_path')
                        ->label('Transfer Confirmation')
                        ->directory('investment-interest-payout-receipts')
                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                        ->columnSpanFull()
                        ->visible(fn (Get $get) => $get('mark_as_paid')),

                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull()
                        ->placeholder('Why this is being backfilled, e.g. paid before the payout schedule was correctly configured on this record.'),
                ];
            })
            ->action(function (array $data) {
                $investment = $this->getOwnerRecord();
                $payoutService = app(InvestmentInterestPayoutService::class);
                $schedule = $payoutService->projectSchedule($investment);
                $row = $schedule[(int) $data['period_index']];

                try {
                    $payout = $payoutService->generateDue($investment, $row['period_start'], $row['period_end'], $row['due_date']);

                    if (filled($data['notes'] ?? null)) {
                        $payout->update(['notes' => $data['notes']]);
                    }

                    if ($data['mark_as_paid'] ?? false) {
                        $payoutService->recordPayment($payout, null, auth()->user(), $data);
                    }

                    Notification::make()
                        ->title(($data['mark_as_paid'] ?? false) ? 'Historical payout recorded and marked paid' : 'Interest recorded as due (not yet paid)')
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Failed to record payout')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Corrects a payout mistakenly marked paid/processing when no cash actually
     * moved — puts it back to 'due' (still earned and owed, just not yet disbursed)
     * rather than 'reversed' (which implies real money left the company and had to be
     * undone). See InvestmentInterestPayoutService::revertToDue().
     */
    protected function revertToDueAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('revertToDue')
            ->label('Revert to Due')
            ->icon('heroicon-o-backspace')
            ->color('warning')
            ->visible(fn ($record) => in_array($record->status->value, ['processing', 'paid']))
            ->form([
                Forms\Components\Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->placeholder('e.g. Marked paid by mistake — this loan only disburses interest at contract maturity.'),
            ])
            ->requiresConfirmation()
            ->modalDescription('Use this when no cash actually left the company — cancels the phantom payment entry and puts the payout back to "Due" (earned, still owed) rather than "Reversed". If cash was genuinely sent to the investor, use Reverse on the Interest Payouts page instead.')
            ->action(function ($record, array $data) {
                try {
                    app(InvestmentInterestPayoutService::class)->revertToDue($record, auth()->user(), $data['reason']);
                    Notification::make()->title('Payout reverted to due')->success()->send();
                } catch (\Throwable $e) {
                    Notification::make()->title('Failed to revert')->body($e->getMessage())->danger()->send();
                }
            });
    }
}

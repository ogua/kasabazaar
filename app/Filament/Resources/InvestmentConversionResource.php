<?php

namespace App\Filament\Resources;

use App\Enums\InvestmentConversionDirection;
use App\Enums\InvestmentConversionSourceMode;
use App\Enums\InvestmentConversionStatus;
use App\Enums\InvestmentPayoutFrequency;
use App\Filament\Resources\InvestmentConversionResource\Pages;
use App\Models\InvestmentConversion;
use App\Notifications\InvestmentConversionStatusUpdated;
use App\Service\InvestmentConversionService;
use App\Service\InvestorNotifier;
use Filament\Actions\Action as PageAction;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Table;

class InvestmentConversionResource extends Resource
{
    protected static ?string $model = InvestmentConversion::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Investors';

    protected static ?string $navigationLabel = 'Capital Conversions';

    protected static ?int $navigationSort = 6;

    protected static bool $isScopedToTenant = false;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', InvestmentConversionStatus::pending_approval->value)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Conversion')
                ->schema([
                    Infolists\Components\TextEntry::make('reference'),
                    Infolists\Components\TextEntry::make('investor.name')->label('Investor'),
                    Infolists\Components\TextEntry::make('direction')->badge(),
                    Infolists\Components\TextEntry::make('status')->badge(),
                    Infolists\Components\TextEntry::make('conversion_date')->date('F j, Y'),
                    Infolists\Components\TextEntry::make('targetInvestment.reference')
                        ->label('Successor Tranche')
                        ->placeholder('Not yet issued'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('Settlement')
                ->schema([
                    Infolists\Components\TextEntry::make('total_principal_rolled')->money('USD'),
                    Infolists\Components\TextEntry::make('total_interest_rolled')->money('USD'),
                    Infolists\Components\TextEntry::make('total_amount')
                        ->label('Total Carried Forward')
                        ->money('USD')
                        ->weight('bold'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('Terms of the Successor Tranche')
                ->schema([
                    Infolists\Components\TextEntry::make('target_contract_term_months')
                        ->label('Contract Term')
                        ->suffix(' months'),
                    Infolists\Components\TextEntry::make('target_payout_frequency')
                        ->label('Payout Frequency')
                        ->badge()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('target_annual_rate')
                        ->label('Annual Rate')
                        ->suffix('%')
                        ->placeholder('Resolved from rate settings'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('Source Tranches')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('sources')
                        ->hiddenLabel()
                        ->schema([
                            Infolists\Components\TextEntry::make('sourceInvestment.reference')->label('Reference'),
                            Infolists\Components\TextEntry::make('mode')->badge(),
                            Infolists\Components\TextEntry::make('principal_at_conversion')->money('USD'),
                            Infolists\Components\TextEntry::make('interest_at_conversion')->money('USD'),
                            Infolists\Components\TextEntry::make('amount_rolled')->money('USD'),
                            Infolists\Components\TextEntry::make('amount_paid_out')
                                ->label('Interest Paid Out')
                                ->money('USD'),
                            Infolists\Components\TextEntry::make('remaining_balance_after')->money('USD'),
                        ])
                        ->columns(4),
                ]),

            Infolists\Components\Section::make('Audit')
                ->schema([
                    Infolists\Components\IconEntry::make('requested_by_investor')->boolean(),
                    Infolists\Components\IconEntry::make('maturity_exception_approved')->boolean(),
                    Infolists\Components\IconEntry::make('threshold_exception_approved')->boolean(),
                    Infolists\Components\TextEntry::make('reviewedBy.name')->placeholder('—'),
                    Infolists\Components\TextEntry::make('executedBy.name')->placeholder('—'),
                    Infolists\Components\TextEntry::make('executed_at')->dateTime()->placeholder('—'),
                    Infolists\Components\TextEntry::make('rejection_reason')->placeholder('—')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('investor.name')
                    ->label('Investor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('direction')->badge(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount Carried Forward')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sources_count')
                    ->label('Tranches')
                    ->counts('sources'),

                Tables\Columns\TextColumn::make('targetInvestment.reference')
                    ->label('Successor')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')->badge(),

                Tables\Columns\IconColumn::make('requested_by_investor')
                    ->label('Investor-raised')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('conversion_date')->date('M j, Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(InvestmentConversionStatus::class),
                Tables\Filters\SelectFilter::make('direction')->options(InvestmentConversionDirection::class),
            ])
            ->actions([
                ...self::reviewActions(),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    /**
     * The four decisions staff can take on a conversion, shared by the table rows and
     * the view page's header so a conversion can be worked from either place. Table
     * rows and page headers use different Action classes, hence the class parameter.
     *
     * @param  class-string<TableAction|PageAction>  $actionClass
     * @return array<int, TableAction|PageAction>
     */
    public static function reviewActions(string $actionClass = TableAction::class): array
    {
        return [
            self::approveAction($actionClass),
            self::rejectAction($actionClass),
            self::executeAction($actionClass),
            self::reverseAction($actionClass),
        ];
    }

    /**
     * Approving only clears the request for execution — it moves no money. Staff may
     * grant the two exceptions here, mirroring how a withdrawal request's
     * exception_approved is granted at approval rather than at submission.
     */
    public static function approveAction(string $actionClass = TableAction::class): TableAction|PageAction
    {
        return $actionClass::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (InvestmentConversion $record) => $record->status === InvestmentConversionStatus::pending_approval)
            ->form([
                Forms\Components\TextInput::make('target_annual_rate')
                    ->label('Annual Rate for the Successor Tranche')
                    ->numeric()
                    ->suffix('%')
                    ->default(fn (InvestmentConversion $record) => $record->target_annual_rate)
                    ->helperText('Required for a loan — a loan carries a fixed rate for its whole term, and without this it would re-price off the company-wide rate settings. Leave blank on an investment tranche to fall back to the standing rates.')
                    ->required(fn (InvestmentConversion $record) => $record->direction === InvestmentConversionDirection::to_loan),

                Forms\Components\Select::make('target_payout_frequency')
                    ->label('Interest Payout Frequency')
                    ->options(InvestmentPayoutFrequency::class)
                    ->default(fn (InvestmentConversion $record) => $record->target_payout_frequency?->value)
                    ->visible(fn (InvestmentConversion $record) => $record->direction === InvestmentConversionDirection::to_loan)
                    ->required(fn (InvestmentConversion $record) => $record->direction === InvestmentConversionDirection::to_loan),

                Forms\Components\Toggle::make('maturity_exception_approved')
                    ->label('Allow conversion before the contract term has elapsed')
                    ->helperText('Only needed if a selected tranche has not yet matured.'),

                Forms\Components\Toggle::make('threshold_exception_approved')
                    ->label('Waive the partial-conversion minimums')
                    ->helperText('Only applies to partial rolls, which must otherwise meet the same floors as a partial withdrawal.'),
            ])
            ->action(function (InvestmentConversion $record, array $data) {
                $record->update([
                    'status' => InvestmentConversionStatus::approved->value,
                    'target_annual_rate' => $data['target_annual_rate'] ?? $record->target_annual_rate,
                    'target_payout_frequency' => $data['target_payout_frequency'] ?? $record->target_payout_frequency?->value,
                    'maturity_exception_approved' => (bool) ($data['maturity_exception_approved'] ?? false),
                    'threshold_exception_approved' => (bool) ($data['threshold_exception_approved'] ?? false),
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);

                InvestorNotifier::notify(
                    $record->investor_id,
                    new InvestmentConversionStatusUpdated($record->fresh())
                );

                Notification::make()
                    ->title('Conversion approved')
                    ->body('Use "Execute" to settle the source tranches and issue the successor.')
                    ->success()
                    ->send();
            });
    }

    public static function rejectAction(string $actionClass = TableAction::class): TableAction|PageAction
    {
        return $actionClass::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (InvestmentConversion $record) => in_array($record->status, [
                InvestmentConversionStatus::pending_approval,
                InvestmentConversionStatus::approved,
            ], true))
            ->form([
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Reason')
                    ->required()
                    ->helperText('Shown to the investor.'),
            ])
            ->action(function (InvestmentConversion $record, array $data) {
                $record->update([
                    'status' => InvestmentConversionStatus::rejected->value,
                    'rejection_reason' => $data['rejection_reason'],
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);

                InvestorNotifier::notify(
                    $record->investor_id,
                    new InvestmentConversionStatusUpdated($record->fresh())
                );

                Notification::make()->title('Conversion rejected')->success()->send();
            });
    }

    public static function executeAction(string $actionClass = TableAction::class): TableAction|PageAction
    {
        return $actionClass::make('execute')
            ->label('Execute')
            ->icon('heroicon-o-play')
            ->color('primary')
            ->visible(fn (InvestmentConversion $record) => $record->status === InvestmentConversionStatus::approved)
            ->requiresConfirmation()
            ->modalHeading('Execute this conversion?')
            ->modalDescription(fn (InvestmentConversion $record) => sprintf(
                'This settles the source tranche(s) — posting interest up to %s first — and issues a new %s tranche for the combined value. %s',
                $record->conversion_date->format('F j, Y'),
                strtolower($record->direction->targetCapitalType()->getLabel()),
                $record->direction === InvestmentConversionDirection::to_loan
                    ? 'Note: once converted to a loan, the investor can no longer request an early withdrawal — loan principal is due in full at maturity.'
                    : ''
            ))
            ->action(function (InvestmentConversion $record) {
                try {
                    $target = app(InvestmentConversionService::class)->execute($record, auth()->user());
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Conversion could not be executed')
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                $record->refresh();

                Notification::make()
                    ->title('Conversion executed')
                    ->body("Issued {$target->reference} for \$".number_format((float) $target->principal_amount, 2).'. Its agreement is ready for the investor to review.')
                    ->success()
                    ->persistent()
                    ->send();
            });
    }

    public static function reverseAction(string $actionClass = TableAction::class): TableAction|PageAction
    {
        return $actionClass::make('reverse')
            ->label('Reverse')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            // Reversing unwinds posted ledger rows and voids an issued agreement — the
            // same bar the interest-credit reversal on TransactionsRelationManager sets.
            ->visible(fn (InvestmentConversion $record) => $record->status === InvestmentConversionStatus::executed
                && (auth()->user()?->hasRole('super_admin') ?? false))
            ->requiresConfirmation()
            ->modalDescription('This restores the source tranches to their pre-conversion balances and removes the successor tranche. Any agreement already issued for the successor becomes void — tell the investor.')
            ->form([
                Forms\Components\Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->placeholder('e.g. Recorded against the wrong tranche, wrong conversion date...'),
            ])
            ->action(function (InvestmentConversion $record, array $data) {
                try {
                    app(InvestmentConversionService::class)->reverse($record, auth()->user(), $data['reason']);
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Conversion could not be reversed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $record->refresh();

                Notification::make()->title('Conversion reversed')->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestmentConversions::route('/'),
            'view' => Pages\ViewInvestmentConversion::route('/{record}'),
        ];
    }

    /**
     * The modes a source tranche may be converted under, for reuse by the conversion
     * form on InvestmentResource and the investor panel.
     *
     * @return array<string, string>
     */
    public static function sourceModeOptions(): array
    {
        return collect(InvestmentConversionSourceMode::cases())
            ->mapWithKeys(fn (InvestmentConversionSourceMode $mode) => [$mode->value => $mode->getLabel()])
            ->all();
    }
}

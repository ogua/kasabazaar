<?php

namespace App\Filament\Investor\Resources;

use App\Enums\InvestmentConversionDirection;
use App\Enums\InvestmentConversionSourceMode;
use App\Enums\InvestmentConversionStatus;
use App\Enums\InvestmentPayoutFrequency;
use App\Enums\InvestmentStatus;
use App\Filament\Investor\Resources\InvestmentConversionResource\Pages;
use App\Models\Investment;
use App\Models\InvestmentConversion;
use App\Service\InvestmentConversionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The investor's own view of capital conversions: raise a request, watch it through
 * review, see the outcome. Requests only — executing one settles real ledger balances
 * and stays with staff, exactly as withdrawal requests do.
 */
class InvestmentConversionResource extends Resource
{
    protected static ?string $model = InvestmentConversion::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Capital Conversions';

    protected static ?string $pluralModelLabel = 'Capital Conversions';

    protected static ?string $modelLabel = 'Capital Conversion';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('investor_id', auth()->user()->investor_id)
            ->orderBy('created_at', 'desc');
    }

    // Investors authorize by ownership (scoped query above), not by the Shield
    // permissions the shared model's policy requires for admin staff.
    public static function canViewAny(): bool
    {
        return filled(auth()->user()?->investor_id);
    }

    public static function canView($record): bool
    {
        return $record->investor_id === auth()->user()->investor_id;
    }

    public static function canCreate(): bool
    {
        return filled(auth()->user()?->investor_id);
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('What would you like to do?')
                    ->schema([
                        Forms\Components\Radio::make('direction')
                            ->hiddenLabel()
                            ->options([
                                InvestmentConversionDirection::to_loan->value => 'Convert an investment into a loan',
                                InvestmentConversionDirection::to_investment->value => 'Convert a loan into an investment',
                            ])
                            ->descriptions([
                                InvestmentConversionDirection::to_loan->value => 'Your capital stops compounding and instead pays you cash interest on a fixed schedule. Note you would no longer be able to request an early withdrawal — loan principal is due in full at maturity.',
                                InvestmentConversionDirection::to_investment->value => 'Your capital stops paying out cash interest and instead compounds annually. Any interest accrued but not yet paid is rolled into the new principal.',
                            ])
                            ->default(InvestmentConversionDirection::to_loan->value)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('source_ids', [])),
                    ]),

                Forms\Components\Section::make('Which holdings?')
                    ->schema([
                        Forms\Components\CheckboxList::make('source_ids')
                            ->hiddenLabel()
                            ->options(fn (Get $get) => self::eligibleOptions($get('direction')))
                            ->required()
                            ->live()
                            ->bulkToggleable()
                            ->helperText('Select one holding, or several to combine them into a single new one.'),
                    ]),

                Forms\Components\Section::make('How much of each?')
                    ->schema([
                        Forms\Components\Radio::make('mode')
                            ->hiddenLabel()
                            ->options(collect(InvestmentConversionSourceMode::cases())
                                ->mapWithKeys(fn (InvestmentConversionSourceMode $mode) => [$mode->value => $mode->getLabel()])
                                ->all())
                            ->default(InvestmentConversionSourceMode::full->value)
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount to convert')
                            ->numeric()
                            ->prefix('USD')
                            ->visible(fn (Get $get) => $get('mode') === InvestmentConversionSourceMode::partial->value)
                            ->required(fn (Get $get) => $get('mode') === InvestmentConversionSourceMode::partial->value)
                            ->helperText(sprintf(
                                'At least $%s, and at least $%s must remain in the holding. A partial amount applies to each holding selected above.',
                                number_format((float) config('investment.partial_minimum'), 2),
                                number_format((float) config('investment.minimum_remaining_balance'), 2)
                            )),

                        Forms\Components\Placeholder::make('quote')
                            ->label('What would be carried forward')
                            ->content(fn (Get $get) => self::quoteSummary($get))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Terms you are requesting')
                    ->description('Our team confirms the rate when they review your request.')
                    ->schema([
                        Forms\Components\TextInput::make('target_contract_term_months')
                            ->label('Contract term (months)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(600)
                            ->default(12)
                            ->required()
                            ->suffix('months'),

                        Forms\Components\Select::make('target_payout_frequency')
                            ->label('How often would you like interest paid?')
                            ->options(InvestmentPayoutFrequency::class)
                            ->default(InvestmentPayoutFrequency::quarterly->value)
                            ->visible(fn (Get $get) => $get('direction') === InvestmentConversionDirection::to_loan->value)
                            ->required(fn (Get $get) => $get('direction') === InvestmentConversionDirection::to_loan->value),

                        Forms\Components\Textarea::make('notes')
                            ->label('Anything you would like us to know?')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Holdings the investor may convert in the chosen direction, labelled with what
     * each would contribute — the same settlement figure the request is booked at.
     *
     * @return array<string, string>
     */
    public static function eligibleOptions(?string $direction): array
    {
        if (! $direction) {
            return [];
        }

        $targetType = InvestmentConversionDirection::from($direction)->targetCapitalType();
        $service = app(InvestmentConversionService::class);

        return Investment::where('investor_id', auth()->user()->investor_id)
            ->excludingConverted()
            ->where('status', InvestmentStatus::active->value)
            ->where('capital_type', '!=', $targetType->value)
            ->orderBy('start_date')
            ->get()
            ->mapWithKeys(function (Investment $investment) use ($service) {
                $settlement = $service->settlementValue($investment, now());
                $total = $settlement['principal'] + $settlement['interest'];

                return [$investment->id => sprintf(
                    '%s — $%s principal + $%s interest = $%s%s',
                    $investment->reference,
                    number_format($settlement['principal'], 2),
                    number_format($settlement['interest'], 2),
                    number_format($total, 2),
                    $investment->isContractDue() ? '' : ' (term not yet elapsed — needs approval)'
                )];
            })
            ->all();
    }

    private static function quoteSummary(Get $get): string
    {
        $sourceIds = $get('source_ids') ?: [];

        if (empty($sourceIds)) {
            return 'Select a holding above.';
        }

        $mode = $get('mode') ?: InvestmentConversionSourceMode::full->value;

        if ($mode === InvestmentConversionSourceMode::partial->value && ! filled($get('amount'))) {
            return 'Enter an amount above.';
        }

        try {
            $quote = app(InvestmentConversionService::class)->quote(
                auth()->user()->investor,
                collect($sourceIds)->map(fn ($id) => [
                    'investment_id' => $id,
                    'mode' => $mode,
                    'amount' => $get('amount'),
                ])->all(),
                now()
            );
        } catch (\Throwable $e) {
            return $e->getMessage();
        }

        return sprintf(
            '$%s principal + $%s interest = $%s carried forward%s',
            number_format($quote['total_principal_rolled'], 2),
            number_format($quote['total_interest_rolled'], 2),
            number_format($quote['total_amount'], 2),
            $quote['total_paid_out'] > 0
                ? sprintf(', with $%s of interest paid out to you in cash', number_format($quote['total_paid_out'], 2))
                : ''
        );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make()
                ->schema([
                    Infolists\Components\TextEntry::make('reference'),
                    Infolists\Components\TextEntry::make('direction')->badge(),
                    Infolists\Components\TextEntry::make('status')->badge(),
                    Infolists\Components\TextEntry::make('conversion_date')->date('F j, Y'),
                ])
                ->columns(4),

            Infolists\Components\Section::make('What is being carried forward')
                ->schema([
                    Infolists\Components\TextEntry::make('total_principal_rolled')->label('Principal')->money('USD'),
                    Infolists\Components\TextEntry::make('total_interest_rolled')->label('Interest')->money('USD'),
                    Infolists\Components\TextEntry::make('total_amount')->label('Total')->money('USD')->weight('bold'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('Holdings')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('sources')
                        ->hiddenLabel()
                        ->schema([
                            Infolists\Components\TextEntry::make('sourceInvestment.reference')->label('Reference'),
                            Infolists\Components\TextEntry::make('mode')->badge(),
                            Infolists\Components\TextEntry::make('amount_rolled')->label('Carried forward')->money('USD'),
                            Infolists\Components\TextEntry::make('amount_paid_out')->label('Paid to you')->money('USD'),
                        ])
                        ->columns(4),
                ]),

            Infolists\Components\Section::make('Your new holding')
                ->schema([
                    Infolists\Components\TextEntry::make('targetInvestment.reference')
                        ->label('Reference')
                        ->placeholder('Issued once approved'),
                    Infolists\Components\TextEntry::make('target_contract_term_months')
                        ->label('Contract term')
                        ->suffix(' months'),
                    Infolists\Components\TextEntry::make('target_payout_frequency')
                        ->label('Interest payouts')
                        ->badge()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('target_annual_rate')
                        ->label('Annual rate')
                        ->suffix('%')
                        ->placeholder('Confirmed on approval'),
                ])
                ->columns(4),

            Infolists\Components\Section::make('Why this was declined')
                ->schema([
                    Infolists\Components\TextEntry::make('rejection_reason')->hiddenLabel(),
                ])
                ->visible(fn (InvestmentConversion $record) => filled($record->rejection_reason)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable(),

                Tables\Columns\TextColumn::make('direction')->badge(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Carried Forward')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('sources_count')
                    ->label('Holdings')
                    ->counts('sources'),

                Tables\Columns\TextColumn::make('targetInvestment.reference')
                    ->label('New Holding')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->date('M d, Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                self::cancelAction(),
            ])
            ->bulkActions([]);
    }

    private static function cancelAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('cancel')
            ->label('Cancel')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Your request will be withdrawn and will not be reviewed. Your holdings are unaffected.')
            ->visible(fn (InvestmentConversion $record) => $record->status === InvestmentConversionStatus::pending_approval)
            ->action(fn (InvestmentConversion $record) => $record->update([
                'status' => InvestmentConversionStatus::cancelled->value,
                'notes' => trim(($record->notes ?? '')."\nCancelled by the investor on ".now()->toDateTimeString().'.'),
            ]));
    }

    /**
     * Loan tranches cannot be withdrawn early, so an investor holding only loans has
     * no route to their capital other than a conversion — surfaced as a nav badge.
     */
    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()
            ->where('status', InvestmentConversionStatus::pending_approval->value)
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestmentConversions::route('/'),
            'create' => Pages\CreateInvestmentConversion::route('/create'),
            'view' => Pages\ViewInvestmentConversion::route('/{record}'),
        ];
    }
}

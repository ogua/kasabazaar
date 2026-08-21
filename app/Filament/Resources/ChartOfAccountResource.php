<?php

namespace App\Filament\Resources;

use App\Enums\AccountSubtype;
use App\Enums\AccountType;
use App\Filament\Resources\ChartOfAccountResource\Pages;
use App\Models\ChartOfAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The account list every financial statement is built from. Seeded from the
 * cashbook's own income and expenditure ledger types (see ChartOfAccountsSeeder), so
 * the accounts here mirror how the business already classifies money rather than
 * introducing a second, competing classification.
 */
class ChartOfAccountResource extends Resource
{
    protected static ?string $model = ChartOfAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Chart of Accounts';

    protected static ?int $navigationSort = 7;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Account')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Stable identifier the statement engine maps onto — e.g. INC-SHIPPING, EXP-MATERIAL, AST-BANK. Changing it on a seeded account will stop live data reaching that line.'),

                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('subtype')
                        ->label('Classification')
                        ->options(AccountSubtype::class)
                        ->required()
                        ->live()
                        // type is derived from subtype rather than picked separately,
                        // so the two can never disagree (a revenue account typed as an
                        // asset would silently corrupt both statements).
                        ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $state
                            ? $set('type', AccountSubtype::from($state)->type()->value)
                            : null)
                        ->helperText('Determines which statement and which caption the account appears under.'),

                    Forms\Components\Select::make('type')
                        ->options(AccountType::class)
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Derived from the classification.'),

                    Forms\Components\TextInput::make('statement_line')
                        ->label('Statement Line')
                        ->required()
                        ->maxLength(255)
                        ->datalist(fn () => ChartOfAccount::query()
                            ->distinct()
                            ->orderBy('statement_line')
                            ->pluck('statement_line')
                            ->all())
                        ->helperText('The caption this rolls up into on the printed statement. Several accounts sharing one line keeps the face of the statement readable while the detail is preserved underneath.'),

                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->helperText('Inactive accounts are excluded from statements and from prior-year entry.'),

                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->defaultGroup('type')
            ->paginated([25, 50, 100, 'all'])
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('name')->searchable(),

                Tables\Columns\TextColumn::make('subtype')
                    ->label('Classification')
                    ->badge(),

                Tables\Columns\TextColumn::make('statement_line')
                    ->label('Statement Line')
                    ->searchable(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Fed By')
                    ->state(fn (ChartOfAccount $record) => self::dataSourceFor($record))
                    ->wrap()
                    ->color('gray')
                    ->tooltip('Where this account\'s figures come from for a derived year.'),

                Tables\Columns\TextColumn::make('balances_count')
                    ->label('Keyed Years')
                    ->counts('balances')
                    ->tooltip('Prior years this account has a manually entered balance for.')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(AccountType::class),
                Tables\Filters\SelectFilter::make('subtype')->label('Classification')->options(AccountSubtype::class),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    /**
     * Plain-English description of where a derived year's figure for this account comes
     * from. Mirrors the mapping in FinancialStatementService — an account with no
     * source here only ever carries manually entered prior-year balances.
     */
    public static function dataSourceFor(ChartOfAccount $account): string
    {
        $viaRecords = \App\Service\FinancialStatementService::tradingSource() === 'records';

        return match (true) {
            $account->code === 'INC-FREIGHT' => $viaRecords ? 'Shipment totals raised in the year' : 'Not used (cashbook source)',
            $account->code === 'INC-SERVICE' => $viaRecords ? 'Income records in mapped categories' : 'Not used (cashbook source)',
            str_starts_with($account->code, 'INC-') && $account->code !== 'INC-OTHER' => $viaRecords ? 'Not used (records source)' : 'Cashbook income ledger',
            $account->code === 'INC-OTHER' => 'Income records (external / unmapped categories)',
            $account->code === 'EXP-SALARIES_WAGES' => 'Payroll entries',
            $account->code === 'EXP-INVESTOR-INTEREST' => 'Posted interest credits on investment tranches',
            $account->code === 'EXP-LOAN-INTEREST' => 'Interest payouts on loan tranches',
            str_starts_with($account->code, 'EXP-') => $viaRecords
                ? 'Expense records in mapped categories'
                : 'Cashbook expenditure ledger',
            $account->code === 'AST-BANK' => 'Cashbook closing bank balance, else carried-forward keyed balance',
            $account->code === 'AST-MOMO' => 'Cashbook closing mobile-money balance, else carried-forward keyed balance',
            $account->code === 'AST-AR' => 'Unpaid shipment balances (total less paid)',
            $account->code === 'AST-FIXED' => 'Accumulated fixed-asset spend (cashbook)',
            $account->code === 'AST-ACC-DEP' => 'Accumulated depreciation (cashbook)',
            $account->code === 'AST-WAREHOUSE-WIP' => 'Accumulated warehouse WIP (cashbook)',
            $account->code === 'LIA-INVESTOR-CAPITAL' => 'Live investment tranche balances',
            $account->code === 'LIA-INVESTOR-LOANS' => 'Live loan tranche principal',
            $account->code === 'LIA-INVESTOR-INTEREST' => 'Unpaid loan interest payouts',
            $account->code === 'LIA-DIRECTOR' => 'Cashbook director account',
            $account->code === 'LIA-LOANS' => 'Cashbook loans',
            $account->code === 'LIA-WHT' => 'Cashbook withholding tax',
            $account->code === 'EQY-RETAINED' => 'Prior-year retained earnings + this year\'s result',
            default => 'Carried-forward keyed balance',
        };
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->active()->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChartOfAccounts::route('/'),
            'create' => Pages\CreateChartOfAccount::route('/create'),
            'edit' => Pages\EditChartOfAccount::route('/{record}/edit'),
        ];
    }
}

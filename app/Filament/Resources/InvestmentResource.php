<?php

namespace App\Filament\Resources;

use App\Enums\InvestmentStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\InvestmentResource\Pages;
use App\Filament\Resources\InvestmentResource\RelationManagers\RateOverridesRelationManager;
use App\Filament\Resources\InvestmentResource\RelationManagers\TransactionsRelationManager;
use App\Models\Investment;
use App\Service\InvestmentInterestService;
use App\Service\InvestmentPaymentService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class InvestmentResource extends Resource
{
    protected static ?string $model = Investment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Investors';

    protected static ?string $navigationLabel = 'Investments';

    protected static ?int $navigationSort = 3;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Investment Details')
                    ->schema([
                        Forms\Components\Select::make('investor_id')
                            ->label('Investor')
                            ->relationship('investor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => request()->query('investor_id')),

                        Forms\Components\TextInput::make('principal_amount')
                            ->label('Principal Amount')
                            ->numeric()
                            ->prefix('USD')
                            ->required()
                            ->disabled(fn (?Investment $record) => $record !== null),

                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->default(now())
                            ->disabled(fn (?Investment $record) => $record !== null),

                        Forms\Components\TextInput::make('contract_term_months')
                            ->label('Contract Term (months)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(600)
                            ->default(12)
                            ->required()
                            ->suffix('months')
                            ->helperText('E.g. 6, 12, 24, 36, 60, 120 for 6mo / 1yr / 2yr / 3yr / 5yr / 10yr terms. The investor may not request a withdrawal until this term has elapsed.')
                            ->disabled(fn (?Investment $record) => $record !== null),

                        Forms\Components\TextInput::make('initial_annual_rate')
                            ->label('Annual Rate (optional)')
                            ->numeric()
                            ->suffix('%')
                            ->helperText("Leave blank to fall back to the investor's standing rate, then the company-wide default for each year.")
                            ->visible(fn (?Investment $record) => $record === null)
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Deposit Recording')
                    ->description('How the principal arrived. Money already collected? Record it manually with proof. Otherwise the investor completes a gateway checkout.')
                    ->schema([
                        Forms\Components\Select::make('deposit_gateway')
                            ->label('Deposit Method')
                            ->options([
                                'manual' => 'Manual (already received)',
                                'paystack' => 'Paystack Checkout',
                                'stripe' => 'Stripe Checkout',
                            ])
                            ->default('manual')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('payment_method')
                            ->options(PaymentMethod::class)
                            ->visible(fn (Get $get) => $get('deposit_gateway') === 'manual')
                            ->required(fn (Get $get) => $get('deposit_gateway') === 'manual'),

                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('deposit_gateway') === 'manual'),

                        Forms\Components\FileUpload::make('receipt_path')
                            ->label('Receipt / Deposit Slip')
                            ->directory('investment-receipts')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('deposit_gateway') === 'manual'),

                        Forms\Components\Placeholder::make('gateway_note')
                            ->label('')
                            ->content('The investor (or staff on their behalf) will complete this deposit via a checkout link once the investment record is saved.')
                            ->visible(fn (Get $get) => in_array($get('deposit_gateway'), ['paystack', 'stripe'])),
                    ])
                    ->columns(2)
                    ->visible(fn (?Investment $record) => $record === null),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('investor.name')
                    ->label('Investor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('principal_amount')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Current Value')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Age')
                    ->since()
                    ->color(fn (Investment $record) => self::isStuckPendingPayment($record) ? 'danger' : null)
                    ->tooltip(fn (Investment $record) => self::isStuckPendingPayment($record)
                        ? 'Stuck: checkout started but no payment webhook was ever recorded. Check Webhook Events or resend the payment link.'
                        : null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deposit_gateway')
                    ->label('Deposit Via')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('maturity_date')
                    ->label('Contract Due')
                    ->date('M d, Y')
                    ->color(fn (Investment $record) => $record->isContractDue() ? 'success' : 'gray')
                    ->tooltip(fn (Investment $record) => $record->isContractDue()
                        ? 'Contract has matured — withdrawal requests are allowed.'
                        : 'Contract not yet due — the investor cannot submit a withdrawal request until this date.')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_interest_posted_year')
                    ->label('Interest Posted Through')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending_payment' => 'Pending Payment',
                        'active' => 'Active',
                        'withdrawn' => 'Withdrawn',
                        'closed' => 'Closed',
                    ]),

                Tables\Filters\Filter::make('stuck_pending_payment')
                    ->label('Stuck (pending payment)')
                    ->query(fn ($query) => $query
                        ->where('status', InvestmentStatus::pending_payment->value)
                        ->where('created_at', '<', now()->subHours(config('investment.stuck_pending_payment_hours')))),
            ])
            ->actions([
                Action::make('generatePaymentLink')
                    ->label('Generate Payment Link')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->visible(fn (Investment $record) => $record->status === InvestmentStatus::pending_payment
                        && in_array($record->deposit_gateway, ['paystack', 'stripe']))
                    ->action(function (Investment $record) {
                        try {
                            $email = $record->investor->email ?? config('mail.from.address');
                            $service = app(InvestmentPaymentService::class);

                            $viewUrl = route('filament.admin.resources.investments.view', [
                                'tenant' => Filament::getTenant(),
                                'record' => $record,
                            ]);
                            $indexUrl = route('filament.admin.resources.investments.index', [
                                'tenant' => Filament::getTenant(),
                            ]);

                            $result = $record->deposit_gateway === 'stripe'
                                ? $service->initiateStripe($record, $email, $viewUrl, $indexUrl)
                                : $service->initiatePaystack($record, $email, $viewUrl);

                            Notification::make()
                                ->title('Payment link generated')
                                ->body($result['url'])
                                ->success()
                                ->persistent()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Failed to generate payment link')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('downloadAgreement')
                    ->label('Download Agreement')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Investment $record) => URL::temporarySignedRoute('investment-agreement', now()->addDay(), [
                        'investment' => $record->id,
                        'as_of' => $record->defaultAsOfDate()->toDateString(),
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('downloadAgreementAsOf')
                    ->label('Download As Of...')
                    ->icon('heroicon-o-calendar')
                    ->color('gray')
                    ->form([
                        Forms\Components\DatePicker::make('as_of')
                            ->label('As Of Date')
                            ->default(fn (Investment $record) => $record->defaultAsOfDate())
                            ->required(),
                    ])
                    ->action(function (Investment $record, array $data) {
                        return redirect(URL::temporarySignedRoute('investment-agreement', now()->addDay(), [
                            'investment' => $record->id,
                            'as_of' => $data['as_of'],
                        ]));
                    }),

                Action::make('postInterest')
                    ->label('Post Interest')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->visible(fn (Investment $record) => $record->status === InvestmentStatus::active)
                    ->form(function (Investment $record) {
                        $from = $record->last_interest_posted_through
                            ? Carbon::parse($record->last_interest_posted_through)->addDay()
                            : Carbon::parse($record->start_date);

                        return [
                            Forms\Components\DatePicker::make('period_start')
                                ->label('From')
                                ->default($from)
                                ->live()
                                ->required(),

                            Forms\Components\DatePicker::make('period_end')
                                ->label('To')
                                ->default(now())
                                ->afterOrEqual('period_start')
                                ->live()
                                ->required(),

                            Forms\Components\Placeholder::make('preview')
                                ->label('Computed Interest')
                                ->content(function (Get $get) use ($record) {
                                    if (! $get('period_start') || ! $get('period_end')) {
                                        return '—';
                                    }

                                    try {
                                        $accrual = app(InvestmentInterestService::class)->periodAccrual(
                                            $record,
                                            Carbon::parse($get('period_start')),
                                            Carbon::parse($get('period_end')),
                                            (float) $record->current_balance
                                        );
                                    } catch (\Throwable $e) {
                                        return $e->getMessage();
                                    }

                                    $lines = collect($accrual['segments'])->map(fn ($segment) => sprintf(
                                        '%d: %s%% (%s) × %d days on $%s = $%s',
                                        $segment['year'],
                                        number_format($segment['rate'], 2),
                                        $segment['rate_source'],
                                        $segment['days_held'],
                                        number_format($segment['balance_start'], 2),
                                        number_format($segment['interest'], 2)
                                    ))->implode("\n");

                                    return sprintf('Total: $%s'."\n".'%s', number_format($accrual['total_interest'], 2), $lines);
                                })
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('override_amount')
                                ->label('Override Amount (optional)')
                                ->numeric()
                                ->prefix('USD')
                                ->helperText('Only applied when the period above resolves to a single calendar-year segment.')
                                ->visible(function (Get $get) use ($record) {
                                    if (! $get('period_start') || ! $get('period_end')) {
                                        return false;
                                    }

                                    try {
                                        $accrual = app(InvestmentInterestService::class)->periodAccrual(
                                            $record,
                                            Carbon::parse($get('period_start')),
                                            Carbon::parse($get('period_end')),
                                            (float) $record->current_balance
                                        );
                                    } catch (\Throwable $e) {
                                        return false;
                                    }

                                    return count($accrual['segments']) === 1;
                                }),
                        ];
                    })
                    ->action(function (Investment $record, array $data) {
                        $service = app(InvestmentInterestService::class);

                        try {
                            $drafts = $service->generateDraftForPeriod(
                                $record,
                                Carbon::parse($data['period_start']),
                                Carbon::parse($data['period_end'])
                            );

                            $overrides = [];
                            if (filled($data['override_amount'] ?? null) && $drafts->count() === 1) {
                                $overrides[$drafts->first()->id] = (float) $data['override_amount'];
                            }

                            $service->postDraftBatch($drafts, auth()->user(), $overrides);

                            Notification::make()
                                ->title("Interest posted for {$data['period_start']} – {$data['period_end']}")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Failed to post interest')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
            RateOverridesRelationManager::class,
        ];
    }

    private static function isStuckPendingPayment(Investment $record): bool
    {
        return $record->status === InvestmentStatus::pending_payment
            && $record->created_at->lt(now()->subHours(config('investment.stuck_pending_payment_hours')));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestments::route('/'),
            'create' => Pages\CreateInvestment::route('/create'),
            'view' => Pages\ViewInvestment::route('/{record}'),
            'edit' => Pages\EditInvestment::route('/{record}/edit'),
        ];
    }
}

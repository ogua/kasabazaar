<?php

namespace App\Filament\Resources;

use App\Enums\InvestmentAgreementStatus;
use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentPayoutFrequency;
use App\Enums\InvestmentStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\InvestmentResource\Pages;
use App\Filament\Resources\InvestmentResource\RelationManagers\InterestPayoutsRelationManager;
use App\Filament\Resources\InvestmentResource\RelationManagers\RateOverridesRelationManager;
use App\Filament\Resources\InvestmentResource\RelationManagers\TransactionsRelationManager;
use App\Models\Investment;
use App\Service\InvestmentInterestService;
use App\Service\InvestmentPaymentService;
use App\Service\InvestmentRateResolver;
use Carbon\Carbon;
use Filament\Actions\Action as HeaderAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
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
            ->schema(self::InvestmentForm());
    }

    public static function InvestmentForm()
    {
        return [
            Forms\Components\Section::make('Investment Details')
                ->schema([
                    Forms\Components\Select::make('investor_id')
                        ->label('Investor')
                        ->relationship('investor', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(fn () => request()->query('investor_id')),

                    Forms\Components\Select::make('capital_type')
                        ->label('Capital Type')
                        ->options(InvestmentCapitalType::class)
                        ->default(InvestmentCapitalType::investment)
                        ->required()
                        ->live()
                        ->helperText('Investment: the investor holds a stake and earns returns, compounding annually. Loan: the company owes this back to the lender under loan terms, paid out as periodic cash interest below.'),

                    Forms\Components\Select::make('payout_frequency')
                        ->label('Interest Payout Frequency')
                        ->options(InvestmentPayoutFrequency::class)
                        ->live()
                        ->visible(fn (Get $get) => $get('capital_type') === InvestmentCapitalType::loan->value)
                        ->required(fn (Get $get) => $get('capital_type') === InvestmentCapitalType::loan->value)
                        ->helperText('How often cash interest is paid out to the lender. Unlike an Investment, a Loan\'s interest never compounds into its balance.'),

                    Forms\Components\DatePicker::make('next_payout_due_date')
                        ->label('First Interest Payout Due')
                        ->visible(fn (Get $get) => $get('capital_type') === InvestmentCapitalType::loan->value)
                        ->required(fn (Get $get) => $get('capital_type') === InvestmentCapitalType::loan->value)
                        ->default(fn (Get $get) => $get('start_date') && $get('payout_frequency')
                            ? Carbon::parse($get('start_date'))->addMonths(InvestmentPayoutFrequency::from($get('payout_frequency'))->months())
                            : null)
                        ->helperText('Subsequent payouts recur automatically at the frequency above thereafter.'),

                    Forms\Components\TextInput::make('principal_amount')
                        ->label('Principal Amount')
                        ->numeric()
                        ->prefix('USD')
                        ->required()
                        ->helperText(fn (?Investment $record) => $record !== null
                            ? 'Adjusting this does not change the current balance already accrued — update that separately if needed.'
                            : null),

                    Forms\Components\DatePicker::make('start_date')
                        ->required()
                        ->default(now())
                        ->helperText(fn (?Investment $record) => $record !== null
                            ? 'Changing this recalculates the contract due date from the term below.'
                            : null),

                    Forms\Components\TextInput::make('contract_term_months')
                        ->label('Contract Term (months)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(600)
                        ->default(12)
                        ->required()
                        ->suffix('months')
                        ->helperText('E.g. 6, 12, 24, 36, 60, 120 for 6mo / 1yr / 2yr / 3yr / 5yr / 10yr terms. The investor may not request a withdrawal until this term has elapsed. Changing this recalculates the contract due date.'),

                    Forms\Components\DatePicker::make('maturity_date')
                        ->label('Maturity / Contract Due Date')
                        ->default(fn (Get $get) => $get('start_date') && $get('contract_term_months')
                            ? Carbon::parse($get('start_date'))->addMonths((int) $get('contract_term_months'))
                            : null)
                        ->helperText('Auto-calculated from the start date and term above. Override only to match the exact end date on an already-signed physical agreement — the interest payout schedule and agreement PDF will follow this date.'),

                    Forms\Components\TextInput::make('initial_annual_rate')
                        ->label(fn (?Investment $record) => $record === null
                            ? 'Annual Rate (optional)'
                            : 'Annual Rate for '.Carbon::parse($record->start_date)->year)
                        ->numeric()
                        ->suffix('%')
                        ->helperText(fn (?Investment $record) => $record === null
                            ? "Leave blank to fall back to the investor's standing rate, then the company-wide default for each year."
                            : "Sets the rate override for the investment's start year. Leave blank to make no change here — edit the Rate Overrides tab for other years.")
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, ?Investment $record) {
                            if ($record === null) {
                                return;
                            }

                            $override = $record->rateOverrides()
                                ->where('year', Carbon::parse($record->start_date)->year)
                                ->first();

                            $component->state($override?->annual_rate);
                        }),
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
        ];
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
                    ->sortable()
                    ->tooltip(fn (Investment $record) => $record->capital_type === InvestmentCapitalType::loan
                        ? 'Principal only — a loan\'s balance never compounds. See the Interest Owed column for accrued interest.'
                        : null),

                Tables\Columns\TextColumn::make('interest_owed')
                    ->label('Interest Owed')
                    ->state(fn (Investment $record) => $record->capital_type === InvestmentCapitalType::loan
                        ? $record->interestPayouts()
                            ->whereIn('status', ['due', 'processing'])
                            ->get()
                            ->sum(fn ($payout) => (float) $payout->amount - (float) $payout->amount_paid)
                        : null)
                    ->money('USD')
                    ->placeholder('—')
                    ->tooltip('Loan interest accrued but not yet paid to the lender.')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate')
                    ->state(fn (Investment $record) => self::resolveCurrentRate($record)['rate'] ?? null)
                    ->formatStateUsing(fn (?float $state) => $state !== null ? number_format($state, 2).'%' : '—')
                    ->tooltip(function (Investment $record) {
                        return match (self::resolveCurrentRate($record)['source'] ?? null) {
                            'override' => 'One-off rate override for '.now()->year,
                            'investor_default' => "The investor's standing rate",
                            'company_default' => 'Company-wide default rate for '.now()->year,
                            default => 'No rate configured for '.now()->year,
                        };
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('capital_type')
                    ->label('Capital Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('agreement_status')
                    ->label('Agreement')
                    ->badge()
                    ->toggleable(),

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

                Tables\Filters\SelectFilter::make('capital_type')
                    ->label('Capital Type')
                    ->options(InvestmentCapitalType::class),

                Tables\Filters\SelectFilter::make('agreement_status')
                    ->label('Agreement')
                    ->options(InvestmentAgreementStatus::class),

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

                self::finalizeAgreementAction(),
                self::rejectAgreementAction(),

                self::postInterestAction(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
        // No bulk delete: deleting a funded investment must go through the
        // guarded single-record flow on the Edit page (plain delete when nothing
        // has posted yet, "Reverse & Close" otherwise) — a bulk action can't
        // apply that guard per-record safely.
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
            RateOverridesRelationManager::class,
            InterestPayoutsRelationManager::class,
        ];
    }

    /**
     * Shared "Post Interest" action, reused on the table row, the View page, and the
     * Edit page. Posts exactly the From/To range entered — as a single flat-rate
     * ledger entry, never split at a Dec 31 boundary or extended beyond what was
     * entered — at a rate staff type in themselves rather than one silently resolved
     * from the configured rate settings.
     */
    public static function postInterestAction(): Action
    {
        return self::configurePostInterestAction(Action::make('postInterest'));
    }

    /**
     * Header-action variant of postInterestAction() — the View/Edit page header expects
     * Filament\Actions\Action, which Filament\Tables\Actions\Action does not extend.
     */
    public static function postInterestHeaderAction(): HeaderAction
    {
        return self::configurePostInterestAction(HeaderAction::make('postInterest'));
    }

    /**
     * @template TAction of Action|HeaderAction
     *
     * @param  TAction  $action
     * @return TAction
     */
    private static function configurePostInterestAction(Action|HeaderAction $action): Action|HeaderAction
    {
        return $action
            ->label('Post Interest')
            ->icon('heroicon-o-calculator')
            ->color('info')
            ->visible(fn (Investment $record) => $record->status === InvestmentStatus::active
                && $record->capital_type === InvestmentCapitalType::investment)
            ->form(function (Investment $record) {
                $from = $record->last_interest_posted_through
                    ? Carbon::parse($record->last_interest_posted_through)->addDay()
                    : Carbon::parse($record->start_date);

                $suggestedRate = null;
                try {
                    $suggestedRate = app(InvestmentRateResolver::class)->resolve($record, $from->year)['rate'];
                } catch (\Throwable $e) {
                    // No configured rate for this year — leave blank, staff must enter one.
                }

                return [
                    Forms\Components\DatePicker::make('period_start')
                        ->label('From')
                        ->default($from)
                        ->live()
                        ->required(),

                    Forms\Components\DatePicker::make('period_end')
                        ->label('To')
                        ->afterOrEqual('period_start')
                        ->live()
                        ->required()
                        ->helperText('Interest is posted for exactly this range only — nothing before or after it is calculated or added.'),

                    Forms\Components\TextInput::make('rate')
                        ->label('Annual Rate')
                        ->numeric()
                        ->suffix('%')
                        ->default($suggestedRate)
                        ->required()
                        ->helperText('Applied flat across the whole period above (no splitting or compounding at year boundaries). Change it if the agreed rate differs from the configured default.'),

                    Forms\Components\Placeholder::make('preview')
                        ->label('Computed Interest')
                        ->content(function (Get $get) use ($record) {
                            if (! $get('period_start') || ! $get('period_end') || ! filled($get('rate'))) {
                                return '—';
                            }

                            $periodStart = Carbon::parse($get('period_start'));
                            $periodEnd = Carbon::parse($get('period_end'));

                            if ($periodStart->greaterThan($periodEnd)) {
                                return 'From date must not be after the To date.';
                            }

                            $daysHeld = $periodStart->diffInDays($periodEnd) + 1;
                            $balance = (float) $record->current_balance;
                            $rate = (float) $get('rate');
                            $interest = round($balance * ($rate / 100) * ($daysHeld / 365), 2);

                            return sprintf(
                                '%s%% × %d days on $%s = $%s',
                                number_format($rate, 2),
                                $daysHeld,
                                number_format($balance, 2),
                                number_format($interest, 2)
                            );
                        })
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('override_amount')
                        ->label('Override Amount (optional)')
                        ->numeric()
                        ->prefix('USD')
                        ->helperText('Overrides the computed interest above with this exact dollar amount.'),
                ];
            })
            ->action(function (Investment $record, array $data) {
                $service = app(InvestmentInterestService::class);

                try {
                    $draft = $service->generateManualDraft(
                        $record,
                        Carbon::parse($data['period_start']),
                        Carbon::parse($data['period_end']),
                        (float) $data['rate']
                    );

                    $service->postDraft(
                        $draft,
                        auth()->user(),
                        filled($data['override_amount'] ?? null) ? (float) $data['override_amount'] : null
                    );

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
            });
    }

    /**
     * Staff review action for a signed agreement the investor has uploaded — confirms
     * receipt of a properly countersigned agreement and locks the record in as complete.
     */
    public static function finalizeAgreementAction(): Action
    {
        return Action::make('finalizeAgreement')
            ->label('Finalize Agreement')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (Investment $record) => $record->agreement_status === InvestmentAgreementStatus::pending_review)
            ->requiresConfirmation()
            ->modalDescription('Confirm the uploaded document is a properly signed copy of the agreement before finalizing.')
            ->form([
                Forms\Components\Placeholder::make('signed_copy')
                    ->label('Uploaded Signed Copy')
                    ->content(fn (Investment $record) => new \Illuminate\Support\HtmlString(
                        $record->signed_agreement_path
                            ? '<a href="'.Storage::disk('public')->url($record->signed_agreement_path).'" target="_blank" class="text-primary-600 underline">Open uploaded document</a>'
                            : 'No document on file.'
                    )),
            ])
            ->action(function (Investment $record) {
                $record->update([
                    'agreement_status' => InvestmentAgreementStatus::finalized,
                    'agreement_finalized_at' => now(),
                    'agreement_finalized_by' => auth()->id(),
                ]);

                Notification::make()
                    ->title('Agreement finalized')
                    ->success()
                    ->send();
            });
    }

    /**
     * Sends the uploaded copy back to the investor — e.g. illegible scan, wrong pages
     * signed — resetting the record so they see the "Upload Signed Agreement" action
     * again in their portal.
     */
    public static function rejectAgreementAction(): Action
    {
        return Action::make('rejectAgreement')
            ->label('Reject Signed Copy')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Investment $record) => $record->agreement_status === InvestmentAgreementStatus::pending_review)
            ->form([
                Forms\Components\Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->placeholder('e.g. Signature page missing, scan illegible...'),
            ])
            ->action(function (Investment $record, array $data) {
                $record->update([
                    'agreement_status' => InvestmentAgreementStatus::unsigned,
                    'signed_agreement_path' => null,
                    'agreement_signed_at' => null,
                    'notes' => trim(($record->notes ?? '')."\n[".now()->format('M j, Y').'] Signed agreement rejected: '.$data['reason']),
                ]);

                Notification::make()
                    ->title('Signed copy rejected')
                    ->body('The investor will need to re-upload a signed agreement.')
                    ->warning()
                    ->send();
            });
    }

    private static function isStuckPendingPayment(Investment $record): bool
    {
        return $record->status === InvestmentStatus::pending_payment
            && $record->created_at->lt(now()->subHours(config('investment.stuck_pending_payment_hours')));
    }

    /**
     * @return array{rate: float, source: string}|null
     */
    private static function resolveCurrentRate(Investment $record): ?array
    {
        try {
            return app(InvestmentRateResolver::class)->resolve($record, now()->year);
        } catch (\App\Exceptions\MissingInvestmentRateException) {
            return null;
        }
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

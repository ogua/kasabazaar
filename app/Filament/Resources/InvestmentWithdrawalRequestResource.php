<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Filament\Resources\InvestmentWithdrawalRequestResource\Pages;
use App\Models\InvestmentWithdrawalRequest;
use App\Service\InvestmentTransferService;
use App\Services\InvestmentWithdrawalApprovalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class InvestmentWithdrawalRequestResource extends Resource
{
    protected static ?string $model = InvestmentWithdrawalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Investors';

    protected static ?string $navigationLabel = 'Withdrawal Requests';

    protected static ?int $navigationSort = 5;

    protected static bool $isScopedToTenant = false;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['submitted', 'under_review'])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\Select::make('investor_id')
                            ->label('Investor')
                            ->relationship('investor', 'name')
                            ->disabled(),

                        Forms\Components\Select::make('investment_id')
                            ->label('Investment')
                            ->relationship('investment', 'reference')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'submitted' => 'Submitted',
                                'under_review' => 'Under Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'processing' => 'Processing',
                                'paid' => 'Paid',
                            ])
                            ->disabled(),

                        Forms\Components\Toggle::make('is_full_withdrawal')
                            ->label('Full Withdrawal')
                            ->disabled(),

                        Forms\Components\TextInput::make('requested_amount')
                            ->numeric()
                            ->prefix('USD')
                            ->disabled(),

                        Forms\Components\TextInput::make('amount_paid')
                            ->numeric()
                            ->prefix('USD')
                            ->disabled(),

                        Forms\Components\DatePicker::make('notice_date')
                            ->disabled(),

                        Forms\Components\DatePicker::make('required_action_by')
                            ->disabled(),

                        Forms\Components\DatePicker::make('payment_due_by')
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->disabled(),

                        Forms\Components\Textarea::make('deferral_reason')
                            ->disabled(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('investor.name')
                    ->label('Investor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('investment.reference')
                    ->label('Investment')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_full_withdrawal')
                    ->label('Full')
                    ->boolean(),

                Tables\Columns\TextColumn::make('requested_amount')
                    ->money('USD')
                    ->placeholder('Full balance'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('notice_date')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('required_action_by')
                    ->label('Notice Ends')
                    ->date('M d, Y')
                    ->color(fn ($record) => $record->required_action_by?->isPast() && in_array($record->status->value, ['submitted', 'under_review']) ? 'danger' : null)
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_due_by')
                    ->date('M d, Y')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'under_review' => 'Under Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'processing' => 'Processing',
                        'paid' => 'Paid',
                    ]),
            ])
            ->actions([
                Action::make('markUnderReview')
                    ->label('Mark Under Review')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record) => $record->status->value === 'submitted')
                    ->action(function ($record) {
                        app(InvestmentWithdrawalApprovalService::class)->markUnderReview($record);
                        Notification::make()->title('Marked as Under Review')->success()->send();
                    }),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status->value, ['submitted', 'under_review']))
                    ->form([
                        Forms\Components\Toggle::make('exception_approved')
                            ->label('Approve exception to minimum remaining balance')
                            ->default(fn ($record) => $record->exception_approved)
                            ->visible(fn ($record) => ! $record->is_full_withdrawal
                                && $record->remaining_balance_after !== null
                                && (float) $record->remaining_balance_after < config('investment.minimum_remaining_balance')),

                        Forms\Components\TextInput::make('deferral_days')
                            ->label('Defer Payment (days)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(config('investment.max_deferral_days'))
                            ->default(0)
                            ->live(),

                        Forms\Components\Textarea::make('deferral_reason')
                            ->label('Reason for Deferral')
                            ->requiredIf('deferral_days', fn ($state) => (int) $state > 0)
                            ->visible(fn (Get $get) => (int) $get('deferral_days') > 0),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            app(InvestmentWithdrawalApprovalService::class)->approve($record, auth()->user(), $data);
                            Notification::make()->title('Withdrawal Request Approved')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Approval Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => ! in_array($record->status->value, ['rejected', 'paid']))
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->required()
                            ->label('Reason for Rejection'),
                    ])
                    ->action(function ($record, array $data) {
                        app(InvestmentWithdrawalApprovalService::class)->reject($record, auth()->user(), $data['rejection_reason']);
                        Notification::make()->title('Withdrawal Request Rejected')->warning()->send();
                    }),

                Action::make('sendViaPaystack')
                    ->label('Send via Paystack')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalDescription('This initiates a real Paystack transfer to the investor\'s bank account. The request will move to Processing until the transfer is confirmed.')
                    ->visible(fn ($record) => $record->status->value === 'approved')
                    ->action(function ($record) {
                        try {
                            app(InvestmentTransferService::class)->initiatePaystackTransfer($record, auth()->user());
                            Notification::make()->title('Paystack transfer initiated')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Failed to initiate transfer')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('recordPayment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->visible(fn ($record) => in_array($record->status->value, ['approved', 'processing']))
                    ->form([
                        Forms\Components\Placeholder::make('full_withdrawal_note')
                            ->label('')
                            ->content('The exact payout amount will be computed on submit, including any interest accrued today — the figure below is only an estimate.')
                            ->visible(fn ($record) => $record->is_full_withdrawal),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('USD')
                            ->required()
                            ->disabled(fn ($record) => $record->is_full_withdrawal)
                            ->dehydrated(fn ($record) => ! $record->is_full_withdrawal)
                            ->default(fn ($record) => $record->is_full_withdrawal
                                ? $record->investment->current_balance
                                : (float) $record->requested_amount - (float) $record->amount_paid),

                        Forms\Components\Select::make('payout_gateway')
                            ->label('Payout Method')
                            ->options([
                                'manual' => 'Manual (bank transfer / cheque executed outside the app)',
                                'paystack' => 'Paystack Transfer',
                                'stripe' => 'Stripe (not yet automated — record manually once completed)',
                            ])
                            ->default('manual')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('payment_method')
                            ->options(PaymentMethod::class)
                            ->visible(fn (Get $get) => $get('payout_gateway') === 'manual')
                            ->required(fn (Get $get) => $get('payout_gateway') === 'manual'),

                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('receipt_path')
                            ->label('Transfer Confirmation')
                            ->directory('investment-withdrawal-receipts')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->columnSpanFull(),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            app(InvestmentWithdrawalApprovalService::class)->recordPayment(
                                $record,
                                isset($data['amount']) ? (float) $data['amount'] : null,
                                auth()->user(),
                                $data
                            );
                            Notification::make()->title('Payment Recorded')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Failed to Record Payment')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestmentWithdrawalRequests::route('/'),
            'view' => Pages\ViewInvestmentWithdrawalRequest::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Filament\Resources\InvestmentInterestPayoutResource\Pages;
use App\Models\InvestmentInterestPayout;
use App\Service\InvestmentInterestPayoutService;
use App\Service\InvestmentTransferService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class InvestmentInterestPayoutResource extends Resource
{
    protected static ?string $model = InvestmentInterestPayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Investors';

    protected static ?string $navigationLabel = 'Interest Payouts';

    protected static ?int $navigationSort = 6;

    protected static bool $isScopedToTenant = false;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'due')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payout Details')
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
                                'due' => 'Due',
                                'processing' => 'Processing',
                                'paid' => 'Paid',
                                'skipped' => 'Skipped',
                                'reversed' => 'Reversed',
                            ])
                            ->disabled(),

                        Forms\Components\DatePicker::make('period_start')
                            ->disabled(),

                        Forms\Components\DatePicker::make('period_end')
                            ->disabled(),

                        Forms\Components\DatePicker::make('due_date')
                            ->disabled(),

                        Forms\Components\TextInput::make('rate_applied')
                            ->numeric()
                            ->suffix('%')
                            ->disabled(),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('USD')
                            ->disabled(),

                        Forms\Components\TextInput::make('amount_paid')
                            ->numeric()
                            ->prefix('USD')
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->disabled()
                            ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn ($record) => $record->period_start->format('M d, Y').' – '.$record->period_end->format('M d, Y')),

                Tables\Columns\TextColumn::make('due_date')
                    ->date('M d, Y')
                    ->color(fn (InvestmentInterestPayout $record) => $record->due_date->isPast() && $record->status->value === 'due' ? 'danger' : null)
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('USD')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'due' => 'Due',
                        'processing' => 'Processing',
                        'paid' => 'Paid',
                        'skipped' => 'Skipped',
                        'reversed' => 'Reversed',
                    ]),
            ])
            ->actions([
                Action::make('recordPayment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->visible(fn (InvestmentInterestPayout $record) => in_array($record->status->value, ['due', 'processing']))
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('USD')
                            ->required()
                            ->default(fn (InvestmentInterestPayout $record) => (float) $record->amount - (float) $record->amount_paid),

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
                            ->directory('investment-interest-payout-receipts')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->columnSpanFull(),
                    ])
                    ->action(function (InvestmentInterestPayout $record, array $data) {
                        try {
                            app(InvestmentInterestPayoutService::class)->recordPayment(
                                $record,
                                (float) $data['amount'],
                                auth()->user(),
                                $data
                            );

                            Notification::make()->title('Payment Recorded')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Failed to Record Payment')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('sendViaPaystack')
                    ->label('Send via Paystack')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalDescription('This initiates a real Paystack transfer to the investor\'s bank account. The payout will move to Processing until the transfer is confirmed.')
                    ->visible(fn (InvestmentInterestPayout $record) => $record->status->value === 'due')
                    ->action(function (InvestmentInterestPayout $record) {
                        try {
                            app(InvestmentTransferService::class)->initiatePaystackInterestPayout($record, auth()->user());
                            Notification::make()->title('Paystack transfer initiated')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Failed to initiate transfer')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('markSkipped')
                    ->label('Skip')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->visible(fn (InvestmentInterestPayout $record) => $record->status->value === 'due')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (InvestmentInterestPayout $record, array $data) {
                        app(InvestmentInterestPayoutService::class)->markSkipped($record, auth()->user(), $data['reason']);
                        Notification::make()->title('Payout marked skipped')->warning()->send();
                    }),

                Action::make('reversePayout')
                    ->label('Reverse')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (InvestmentInterestPayout $record) => in_array($record->status->value, ['processing', 'paid']))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->placeholder('e.g. Wrong amount computed, wrong investment...'),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('Records an offsetting entry and marks this payout reversed. This does NOT claw back money already sent to the investor — if cash has already left the company, resolve that separately.')
                    ->action(function (InvestmentInterestPayout $record, array $data) {
                        try {
                            app(InvestmentInterestPayoutService::class)->reversePayout($record, auth()->user(), $data['reason']);
                            Notification::make()->title('Payout reversed')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Failed to reverse')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('revertToDue')
                    ->label('Revert to Due (Not Actually Paid)')
                    ->icon('heroicon-o-backspace')
                    ->color('warning')
                    ->visible(fn (InvestmentInterestPayout $record) => in_array($record->status->value, ['processing', 'paid']))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->placeholder('e.g. Marked paid by mistake — this loan only disburses interest at contract maturity.'),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('Use this when no cash actually left the company — cancels the phantom payment entry and puts the payout back to "Due" (earned, still owed) rather than "Reversed". If cash was genuinely sent to the investor, use Reverse instead.')
                    ->action(function (InvestmentInterestPayout $record, array $data) {
                        try {
                            app(InvestmentInterestPayoutService::class)->revertToDue($record, auth()->user(), $data['reason']);
                            Notification::make()->title('Payout reverted to due')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Failed to revert')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Discard')
                    ->visible(fn (InvestmentInterestPayout $record) => $record->status->value === 'due'),

                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('due_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestmentInterestPayouts::route('/'),
            'view' => Pages\ViewInvestmentInterestPayout::route('/{record}'),
        ];
    }
}

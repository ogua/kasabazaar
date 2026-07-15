<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Shipment;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                    ->description('')
                    ->schema([
                        Forms\Components\TextInput::make('shipment.client.name')
                            ->label('Client')
                            ->required()
                            ->maxLength(36),
                        Forms\Components\TextInput::make('total_amount')
                            ->required()
                            ->badge()
                            ->numeric(),
                        Forms\Components\TextInput::make('status')
                            ->badge()
                            ->required(),
                    ])
                    ->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl('')
            ->columns([
                Tables\Columns\TextColumn::make('shipment.client.name')
                    ->label('Client')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->state(fn ($record) => number_format($record->total_amount, 2))
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->prefix('$')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Paid Amount')
                    ->state(fn ($record) => number_format($record->shipment?->payments->sum('amount'), 2))
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->prefix('$')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_due')
                    ->label('Payment Due')
                    ->state(fn ($record) => number_format($record->shipment?->total - $record->shipment?->paid, 2))
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->prefix('$')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('payments')
                    ->label('Record payments')
                    ->color('info')
                    ->icon('heroicon-m-banknotes')
                    ->visible(fn () => Auth::user()?->hasAnyRole(['super_admin', 'CEO', 'Accountant']))
                    ->modalWidth('4xl')
                    ->fillForm(function ($record) {
                        //  logger($record->shipment);
                        return [
                            'total_amount' => $record->shipment?->total,
                            'paid_amount' => $record->shipment?->paid,
                            'balance' => $record->shipment?->total - $record->shipment?->paid,
                            'new_currency' => 'USD',
                            'new_exchange_rate' => (function () {
                                try {
                                    return app(\App\Service\ExchangeRateService::class)->getCurrentRate('USD', 'GHS');
                                } catch (\Exception $e) {
                                    return 12.0;
                                }
                            })(),
                        ];
                    })
                   // ->fillForm(fn ($record) => )
                    ->form([
                        Forms\Components\Section::make('Payment Summary')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_display')
                                            ->label('Total Amount')
                                            ->content(fn ($record) => '$'.number_format($record->shipment?->total, 2))
                                            ->extraAttributes(['class' => 'text-lg font-bold']),
                                        Forms\Components\Placeholder::make('paid_display')
                                            ->label('Amount Paid')
                                            ->content(fn ($record) => '$'.number_format($record->shipment?->paid, 2))
                                            ->extraAttributes(['class' => 'text-lg font-bold text-success-600']),
                                        Forms\Components\Placeholder::make('balance_display')
                                            ->label('Balance Due')
                                            ->content(fn ($record) => '$'.number_format($record->shipment?->total - $record->shipment?->paid, 2))
                                            ->extraAttributes(['class' => 'text-lg font-bold text-danger-600']),
                                    ]),
                            ])
                            ->collapsible(),

                        Forms\Components\Section::make('Payment History')
                            ->schema([
                                Forms\Components\Placeholder::make('payment_history')
                                    ->label('')
                                    ->content(function ($record) {
                                        if ($record->shipment?->payments->isEmpty()) {
                                            return 'No payments recorded yet.';
                                        }
                                        $html = '<div class="space-y-2">';
                                        foreach ($record->shipment?->payments as $payment) {
                                            $html .= '<div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-800 rounded">';
                                            $html .= '<span class="font-medium">'.($payment->paying_method ?? 'N/A').'</span>';
                                            $html .= '<span class="text-success-600 font-bold">$'.number_format($payment->amount, 2).'</span>';
                                            $html .= '<span class="text-gray-500 text-sm">'.($payment->paid_on ? \Carbon\Carbon::parse($payment->paid_on)->format('M d, Y H:i') : 'N/A').'</span>';
                                            $html .= '</div>';
                                        }
                                        $html .= '</div>';

                                        return new \Illuminate\Support\HtmlString($html);
                                    }),
                            ])
                            ->collapsible(),

                        Forms\Components\Section::make('Add New Payment')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('new_paid_on')
                                            ->label('Payment Date')
                                            ->default(now())
                                            ->required(),

                                        Forms\Components\Select::make('new_paying_method')
                                            ->label('Payment Method')
                                            ->options([
                                                'CASH' => 'Cash',
                                                'Zelle' => 'Zelle',
                                                'Cash App' => 'Cash App',
                                                'BANK TRANSFER' => 'Bank Transfer',
                                                'CREDIT/DEBIT CARD' => 'Card',
                                                'CHEQUE' => 'Cheque',
                                                'PAYPAL' => 'PayPal',
                                                'WAIVED' => 'Waived',
                                            ])
                                            ->required()
                                            ->live()
                                            ->native(false),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('new_currency')
                                            ->label('Currency Received')
                                            ->options([
                                                'USD' => 'USD - US Dollar',
                                                'GHS' => 'GHS - Ghana Cedis',
                                            ])
                                            ->default('USD')
                                            ->required()
                                            ->live()
                                            ->helperText('Choose the currency the client actually paid in. The other amount below is calculated automatically from the exchange rate.')
                                            ->native(false),

                                        Forms\Components\TextInput::make('new_exchange_rate')
                                            ->label('Exchange Rate (1 USD = ? GHS)')
                                            ->numeric()
                                            ->default(function () {
                                                try {
                                                    return app(\App\Service\ExchangeRateService::class)->getCurrentRate('USD', 'GHS');
                                                } catch (\Exception $e) {
                                                    return 12.0;
                                                }
                                            })
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $rate = (float) $state;
                                                if (! $rate) {
                                                    return;
                                                }

                                                if ($get('new_currency') === 'GHS') {
                                                    $amountGhs = (float) ($get('new_amount_ghs') ?? 0);
                                                    if ($amountGhs) {
                                                        $set('new_amount_usd', round($amountGhs / $rate, 2));
                                                    }
                                                } else {
                                                    $amountUsd = (float) ($get('new_amount_usd') ?? 0);
                                                    if ($amountUsd) {
                                                        $set('new_amount_ghs', round($amountUsd * $rate, 2));
                                                    }
                                                }
                                            })
                                            ->helperText('Editable — defaults to the last synced daily rate, but always confirm it against the actual bank/market rate before saving.')
                                            ->suffix('GHS per USD')
                                            ->required(),
                                    ]),

                                Forms\Components\Placeholder::make('new_exchange_rate_warning')
                                    ->label('')
                                    ->content('⚠️ Please cross-check the exchange rate above against the current bank/market rate before saving. It determines the USD amount recorded against the client\'s balance — an incorrect rate will misstate what the client owes.')
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('new_amount_usd')
                                            ->label('Amount (USD)')
                                            ->numeric()
                                            ->prefix('$')
                                            ->live(onBlur: true)
                                            ->disabled(fn (callable $get) => $get('new_currency') === 'GHS')
                                            ->dehydrated()
                                            ->default(fn ($record) => max(0, ($record->shipment?->total ?? 0) - ($record->shipment?->paid ?? 0)))
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($get('new_currency') === 'GHS') {
                                                    return;
                                                }
                                                $exchangeRate = (float) ($get('new_exchange_rate') ?? 0);
                                                if ($state && $exchangeRate > 0) {
                                                    $set('new_amount_ghs', round($state * $exchangeRate, 2));
                                                }
                                            })
                                            ->required(fn (callable $get) => $get('new_currency') !== 'GHS'),

                                        Forms\Components\TextInput::make('new_amount_ghs')
                                            ->label('Amount (GHS)')
                                            ->numeric()
                                            ->prefix('GH₵')
                                            ->live(onBlur: true)
                                            ->disabled(fn (callable $get) => $get('new_currency') !== 'GHS')
                                            ->dehydrated()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($get('new_currency') !== 'GHS') {
                                                    return;
                                                }
                                                $exchangeRate = (float) ($get('new_exchange_rate') ?? 0);
                                                if ($state && $exchangeRate > 0) {
                                                    $set('new_amount_usd', round($state / $exchangeRate, 2));
                                                }
                                            })
                                            ->required(fn (callable $get) => $get('new_currency') === 'GHS')
                                            ->helperText(fn (callable $get) => $get('new_currency') === 'GHS'
                                                ? 'Enter the amount actually received in Ghana Cedis.'
                                                : 'Calculated automatically from the USD amount.'),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('new_bankname')
                                            ->label('Bank Name')
                                            ->visible(fn ($get): bool => $get('new_paying_method') === 'BANK TRANSFER'),

                                        Forms\Components\TextInput::make('new_accountnumber')
                                            ->label('Account Number')
                                            ->visible(fn ($get): bool => $get('new_paying_method') === 'BANK TRANSFER'),

                                        Forms\Components\TextInput::make('new_cheque_no')
                                            ->label('Cheque Number')
                                            ->visible(fn ($get): bool => $get('new_paying_method') === 'CHEQUE')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Textarea::make('new_payment_note')
                                    ->label('Payment Notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function ($record, array $data) {
                        // Create the new payment
                        if (! empty($data['new_amount_usd']) && $data['new_amount_usd'] > 0) {
                            \App\Models\Payment::create([
                                'branch_id' => Filament::getTenant()->id,
                                'user_id' => auth()->id(),
                                'shipment_id' => $record->shipment?->id,
                                'payment_type' => 'credit',
                                'payment_ref' => 'PAY-'.strtoupper(bin2hex(random_bytes(4))),
                                'paying_method' => $data['new_paying_method'],
                                'currency' => $data['new_currency'] ?? 'USD',
                                'exchange_rate' => $data['new_exchange_rate'] ?? null,
                                'amount_usd' => $data['new_amount_usd'],
                                'amount_ghs' => $data['new_amount_ghs'] ?? null,
                                'paid_on' => $data['new_paid_on'],
                                'bankname' => $data['new_bankname'] ?? null,
                                'accountnumber' => $data['new_accountnumber'] ?? null,
                                'cheque_no' => $data['new_cheque_no'] ?? null,
                                'payment_note' => $data['new_payment_note'] ?? null,
                                'change' => 0,
                            ]);

                            // Update shipment paid amount
                            $shipment = Shipment::where('id', $record->shipment?->id)->first();

                            // $totalPaid = $shipment?->payments()->sum('amount') + $data['new_amount'];
                            $totalPaid = $shipment?->payments()->sum('amount');
                            $shipment->paid = $totalPaid;

                            // Update payment status
                            if ($totalPaid >= $shipment->total) {
                                $shipment->payment_status = 'paid';
                            } elseif ($totalPaid > 0) {
                                $shipment->payment_status = 'partial';
                            } else {
                                $shipment->payment_status = 'pending';
                            }

                            $shipment->save();

                            // Update invoice status if exists
                            if ($shipment->invoice) {
                                $shipment->invoice->update([
                                    'status' => $shipment->payment_status,
                                ]);
                            }

                            $body = ($data['new_currency'] ?? 'USD') === 'GHS'
                                ? 'Payment of GH₵'.number_format($data['new_amount_ghs'], 2).' (equivalent to $'.number_format($data['new_amount_usd'], 2).') has been recorded.'
                                : 'Payment of $'.number_format($data['new_amount_usd'], 2).' has been recorded.';

                            \Filament\Notifications\Notification::make()
                                ->title('Payment Added')
                                ->body($body)
                                ->success()
                                ->send();
                        }
                    })
                    ->modalSubmitActionLabel('Add Payment'),

                Tables\Actions\Action::make('Print Invoice')
                    ->icon('heroicon-m-receipt-percent')
                    ->color('success')
                    ->url(fn ($record) => route('shipping-invoice', $record->shipment_id), shouldOpenInNewTab: true),

                Tables\Actions\Action::make('Print Receipt')
                    ->icon('heroicon-m-printer')
                    ->color('info')
                    ->url(fn ($record) => route('shipping-receipt', $record->shipment_id), shouldOpenInNewTab: true),

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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}

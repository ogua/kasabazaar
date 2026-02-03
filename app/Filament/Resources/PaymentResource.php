<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Payment;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PaymentResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PaymentResource\RelationManagers;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $title = 'Transactions';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('created_at', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\Select::make('shipment_id')
                            ->label('Shipment')
                            ->relationship('shipment', 'shipping_reference')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DateTimePicker::make('paid_on')
                            ->label('Payment Date')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('paying_method')
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

                        Forms\Components\TextInput::make('bankname')
                            ->label('Bank Name')
                            ->visible(fn($get): bool => $get('paying_method') === 'BANK TRANSFER'),

                        Forms\Components\TextInput::make('accountnumber')
                            ->label('Account Number')
                            ->visible(fn($get): bool => $get('paying_method') === 'BANK TRANSFER'),

                        Forms\Components\TextInput::make('cheque_no')
                            ->label('Cheque Number')
                            ->visible(fn($get): bool => $get('paying_method') === 'CHEQUE'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Amount & Exchange Rate')
                    ->schema([
                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options([
                                'USD' => 'USD - US Dollar',
                                'GHS' => 'GHS - Ghana Cedis',
                            ])
                            ->default('USD')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('amount_usd')
                            ->label('Amount (USD)')
                            ->numeric()
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $exchangeRate = (float)($get('exchange_rate') ?? 0);
                                if ($state && $exchangeRate > 0) {
                                    $set('amount_ghs', round($state * $exchangeRate, 2));
                                }
                                $set('amount', $state);
                            })
                            ->required(),

                        Forms\Components\TextInput::make('exchange_rate')
                            ->label('Exchange Rate (USD to GHS)')
                            ->numeric()
                            ->default(function () {
                                try {
                                    $service = app(\App\Service\ExchangeRateService::class);
                                    return $service->getCurrentRate('USD', 'GHS');
                                } catch (\Exception $e) {
                                    return 12.0;
                                }
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $amountUsd = (float)($get('amount_usd') ?? 0);
                                if ($amountUsd && $state) {
                                    $set('amount_ghs', round($amountUsd * $state, 2));
                                }
                            })
                            ->helperText('Current exchange rate')
                            ->suffix('GHS per USD')
                            ->required(),

                        Forms\Components\TextInput::make('amount_ghs')
                            ->label('Amount (GHS)')
                            ->numeric()
                            ->prefix('GH₵')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Calculated automatically'),

                        Forms\Components\Hidden::make('amount')
                            ->default(fn ($get) => $get('amount_usd')),

                        Forms\Components\Textarea::make('payment_note')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('System Fields')
                    ->schema([
                        Forms\Components\Hidden::make('branch_id'),
                        Forms\Components\Hidden::make('user_id'),
                        Forms\Components\Hidden::make('payment_ref')
                            ->default(fn() => 'PAY-' . strtoupper(bin2hex(random_bytes(4)))),
                        Forms\Components\Hidden::make('payment_type')->default('credit'),
                        Forms\Components\Hidden::make('balance')->default(0),
                        Forms\Components\Hidden::make('change')->default(0),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shipment.shipping_reference')
                    ->label('Shipment Ref')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->url(fn($record) => $record->shipment_id
                        ? \App\Filament\Resources\ShipmentResource::getUrl('edit', ['record' => $record->shipment_id])
                        : null),

                Tables\Columns\TextColumn::make('shipment.client.name')
                    ->label('Client')
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_ref')
                    ->label('Payment Ref')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('paying_method')
                    ->label('Method')
                    ->searchable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'CASH' => 'success',
                        'Zelle', 'Cash App' => 'info',
                        'BANK TRANSFER' => 'primary',
                        'CREDIT/DEBIT CARD' => 'warning',
                        'CHEQUE' => 'gray',
                        'PAYPAL' => 'info',
                        'WAIVED' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('bankname')
                    ->label('Bank/Details')
                    ->description(fn($record) => $record->cheque_no ? 'Cheque: ' . $record->cheque_no : null)
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount_usd')
                    ->label('Amount (USD)')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn($state) => '$' . number_format($state ?? 0, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('exchange_rate')
                    ->label('Rate')
                    ->numeric(decimalPlaces: 4)
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_ghs')
                    ->label('Amount (GHS)')
                    ->numeric()
                    ->formatStateUsing(fn($state) => 'GH₵' . number_format($state ?? 0, 2))
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_on')
                    ->label('Payment Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('enteredby.name')
                    ->label('Recorded By')
                    ->badge()
                    ->color('info')
                    ->placeholder('Online Payment')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('paying_method')
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
                    ]),
                Tables\Filters\Filter::make('paid_on')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('paid_on', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('paid_on', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('lg'),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        // Update shipment totals after deleting payment
                        if ($record->shipment) {
                            $shipment = $record->shipment;
                            $totalPaid = $shipment->payments()->sum('amount');
                            $shipment->paid = $totalPaid;

                            if ($totalPaid >= $shipment->total && $shipment->total > 0) {
                                $shipment->payment_status = 'paid';
                            } elseif ($totalPaid > 0) {
                                $shipment->payment_status = 'partial';
                            } else {
                                $shipment->payment_status = 'pending';
                            }

                            $shipment->save();

                            // Update invoice status
                            if ($shipment->invoice) {
                                $shipment->invoice->update([
                                    'status' => $shipment->payment_status,
                                ]);
                            }
                        }
                    }),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}

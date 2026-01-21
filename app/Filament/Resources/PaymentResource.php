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
                Forms\Components\Section::make('')
                ->description('')
                ->schema([
                Forms\Components\TextInput::make('branch_id')
                    ->maxLength(36)
                    ->default(null),
                Forms\Components\TextInput::make('user_id')
                    ->maxLength(36)
                    ->default(null),
                Forms\Components\TextInput::make('shipment_id')
                    ->maxLength(36)
                    ->default(null),
                Forms\Components\TextInput::make('account_id')
                    ->maxLength(36)
                    ->default(null),
                Forms\Components\TextInput::make('payment_type'),
                Forms\Components\TextInput::make('payment_ref')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('paying_type')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('change')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('cheque_no')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('customer_stripe_id')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('charge_id')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('paypal_transaction_id')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('paying_method')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Textarea::make('payment_note')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('bankname')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\DateTimePicker::make('paid_on'),
                Forms\Components\TextInput::make('accountnumber')
                    ->maxLength(255)
                    ->default(null),
                ])
                ->columns(2),

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

                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn($state) => '$' . number_format($state, 2))
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

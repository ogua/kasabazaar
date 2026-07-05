<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use App\Models\Shipment;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\InvoiceResource\Pages;

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
                    ->fillForm(function($record){
                      //  logger($record->shipment);
                        return [
                            'total_amount' => $record->shipment?->total,
                            'paid_amount' => $record->shipment?->paid,
                            'balance' => $record->shipment?->total - $record->shipment?->paid,
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
                                    Forms\Components\Grid::make(3)
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

                                        Forms\Components\TextInput::make('new_amount')
                                            ->label('Amount')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->default(fn ($record) => max(0, $record->total - $record->paid)),
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
                        if (! empty($data['new_amount']) && $data['new_amount'] > 0) {
                            \App\Models\Payment::create([
                                'branch_id' => Filament::getTenant()->id,
                                'user_id' => auth()->id(),
                                'shipment_id' => $record->shipment?->id,
                                'payment_type' => 'credit',
                                'payment_ref' => 'PAY-'.strtoupper(bin2hex(random_bytes(4))),
                                'paying_method' => $data['new_paying_method'],
                                'amount' => $data['new_amount'],
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

                            \Filament\Notifications\Notification::make()
                                ->title('Payment Added')
                                ->body('Payment of $'.number_format($data['new_amount'], 2).' has been recorded.')
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

<?php

namespace App\Filament\Widgets;

use Filament\Forms;
use Filament\Tables;
use App\Models\Shipment;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;
use Livewire\Attributes\On;


class UnpaidShipmentsWidget extends BaseWidget
{
    protected static ?int $sort = 11;
    protected int | string | array $columnSpan = 'full';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $containerNumber = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->containerNumber = null;
    }

    #[On('dashboardFiltersUpdated')]
    public function updateFilters($start_date, $end_date, $container_number = null): void
    {
        $this->startDate = $start_date;
        $this->endDate = $end_date;
        $this->containerNumber = $container_number;
    }

    protected function getTableHeading(): ?string
    {
        return 'Unpaid/Partially Paid Shipments';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->startDate ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->endDate ?? now()->format('Y-m-d');
        $containerNumber = $this->containerNumber;

        return $table
            ->query(
                Shipment::query()
                    ->with(['client', 'payments'])
                    ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE shipments.id = payments.shipment_id AND payment_type = "credit") < total')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->when($containerNumber, function ($query) use ($containerNumber) {
                        $query->where('container_number', $containerNumber);
                    })
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('shipping_reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client')
                    ->searchable()
                    ->default(fn($record) => $record->client?->name ?? 'N/A'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total Amount')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('GHS')
                    ->getStateUsing(function ($record) {
                        return $record->payments()
                            ->where('payment_type', 'credit')
                            ->sum('amount');
                    }),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance Due')
                    ->money('GHS')
                    ->getStateUsing(function ($record) {
                        $paid = $record->payments()
                            ->where('payment_type', 'credit')
                            ->sum('amount');
                        return $record->total - $paid;
                    })
                    ->color('danger')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('record_payment')
                    ->label('Record Payment')
                    ->color('success')
                    ->icon('heroicon-m-banknotes')
                    ->modalWidth('4xl')
                    ->fillForm(fn($record) => [
                        'total_amount' => $record->total,
                        'paid_amount' => $record->paid,
                        'balance' => $record->total - $record->paid,
                    ])
                    ->form([
                        Forms\Components\Section::make('Payment Summary')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_display')
                                            ->label('Total Amount')
                                            ->content(fn($record) => '$' . number_format($record->total, 2))
                                            ->extraAttributes(['class' => 'text-lg font-bold']),
                                        Forms\Components\Placeholder::make('paid_display')
                                            ->label('Amount Paid')
                                            ->content(fn($record) => '$' . number_format($record->paid, 2))
                                            ->extraAttributes(['class' => 'text-lg font-bold text-success-600']),
                                        Forms\Components\Placeholder::make('balance_display')
                                            ->label('Balance Due')
                                            ->content(fn($record) => '$' . number_format($record->total - $record->paid, 2))
                                            ->extraAttributes(['class' => 'text-lg font-bold text-danger-600']),
                                    ]),
                            ])
                            ->collapsible(),

                        Forms\Components\Section::make('Payment History')
                            ->schema([
                                Forms\Components\Placeholder::make('payment_history')
                                    ->label('')
                                    ->content(function ($record) {
                                        if ($record->payments->isEmpty()) {
                                            return 'No payments recorded yet.';
                                        }
                                        $html = '<div class="space-y-2">';
                                        foreach ($record->payments as $payment) {
                                            $html .= '<div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-800 rounded">';
                                            $html .= '<span class="font-medium">' . ($payment->paying_method ?? 'N/A') . '</span>';
                                            $html .= '<span class="text-success-600 font-bold">$' . number_format($payment->amount, 2) . '</span>';
                                            $html .= '<span class="text-gray-500 text-sm">' . ($payment->paid_on ? \Carbon\Carbon::parse($payment->paid_on)->format('M d, Y H:i') : 'N/A') . '</span>';
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
                                            ->default(fn($record) => max(0, $record->total - $record->paid)),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('new_bankname')
                                            ->label('Bank Name')
                                            ->visible(fn($get): bool => $get('new_paying_method') === 'BANK TRANSFER'),

                                        Forms\Components\TextInput::make('new_accountnumber')
                                            ->label('Account Number')
                                            ->visible(fn($get): bool => $get('new_paying_method') === 'BANK TRANSFER'),

                                        Forms\Components\TextInput::make('new_cheque_no')
                                            ->label('Cheque Number')
                                            ->visible(fn($get): bool => $get('new_paying_method') === 'CHEQUE')
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
                        if (!empty($data['new_amount']) && $data['new_amount'] > 0) {
                            \App\Models\Payment::create([
                                'branch_id' => Filament::getTenant()->id,
                                'user_id' => auth()->id(),
                                'shipment_id' => $record->id,
                                'payment_type' => 'credit',
                                'payment_ref' => 'PAY-' . strtoupper(bin2hex(random_bytes(4))),
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
                            $totalPaid = $record->payments()->sum('amount') + $data['new_amount'];
                            $record->paid = $totalPaid;

                            // Update payment status
                            if ($totalPaid >= $record->total) {
                                $record->payment_status = 'paid';
                            } elseif ($totalPaid > 0) {
                                $record->payment_status = 'partial';
                            } else {
                                $record->payment_status = 'pending';
                            }

                            $record->save();

                            // Update invoice status if exists
                            if ($record->invoice) {
                                $record->invoice->update([
                                    'status' => $record->payment_status,
                                ]);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Payment Added')
                                ->body('Payment of $' . number_format($data['new_amount'], 2) . ' has been recorded.')
                                ->success()
                                ->send();
                        }
                    })
                    ->modalSubmitActionLabel('Add Payment'),
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn($record) => route('filament.admin.resources.shipments.edit', [
                        'tenant' => \Filament\Facades\Filament::getTenant(),
                        'record' => $record->id,
                    ])),
            ]);
    }
}

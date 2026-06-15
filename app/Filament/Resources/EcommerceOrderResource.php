<?php

namespace App\Filament\Resources;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Filament\Resources\EcommerceOrderResource\Pages;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderShipment;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\ExchangeRateLog;
use App\Notifications\Ecommerce\EcommerceOrderApproved;
use App\Notifications\Ecommerce\EcommerceOrderDelivered;
use App\Notifications\Ecommerce\EcommerceOrderDispatched;
use App\Notifications\Ecommerce\EcommerceOrderPacked;
use App\Notifications\Ecommerce\EcommerceOrderProcessing;
use App\Services\EcommerceInventoryService;
use App\Services\PushNotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EcommerceOrderResource extends Resource
{
    protected static ?string $model = EcommerceOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 3;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Info')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->disabled(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options(EcommerceOrderStatus::class)
                            ->disabled(),
                        Forms\Components\Select::make('payment_status')
                            ->options(EcommerceOrderPaymentStatus::class)
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Financials')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal_ghs')
                            ->label('Subtotal (GHS)')
                            ->disabled()
                            ->prefix('GH₵'),
                        Forms\Components\TextInput::make('shipping_fee_ghs')
                            ->label('Shipping Fee (GHS)')
                            ->disabled()
                            ->prefix('GH₵'),
                        Forms\Components\TextInput::make('discount_ghs')
                            ->label('Discount (GHS)')
                            ->disabled()
                            ->prefix('GH₵'),
                        Forms\Components\TextInput::make('total_ghs')
                            ->label('Total (GHS)')
                            ->disabled()
                            ->prefix('GH₵'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->disabled()
                            ->rows(3),
                        Forms\Components\Textarea::make('cancelled_reason')
                            ->label('Cancellation Reason')
                            ->disabled()
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Order')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_number')
                            ->badge()
                            ->color('info')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Customer'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Payment')
                            ->badge(),
                        Infolists\Components\TextEntry::make('subtotal_ghs')
                            ->label('Subtotal')
                            ->money('GHS'),
                        Infolists\Components\TextEntry::make('shipping_fee_ghs')
                            ->label('Shipping Fee')
                            ->money('GHS'),
                        Infolists\Components\TextEntry::make('total_ghs')
                            ->label('Total')
                            ->money('GHS')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('payment_gateway')
                            ->label('Gateway')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('cancelled_reason')
                            ->label('Cancellation Reason')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->visible(fn (EcommerceOrder $record) => $record->status === EcommerceOrderStatus::Cancelled),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('product_name')
                                    ->label('Product'),
                                Infolists\Components\TextEntry::make('product_sku')
                                    ->label('SKU')
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Qty'),
                                Infolists\Components\TextEntry::make('unit_price_ghs')
                                    ->label('Unit Price')
                                    ->money('GHS'),
                                Infolists\Components\TextEntry::make('total_ghs')
                                    ->label('Line Total')
                                    ->money('GHS'),
                            ])
                            ->columns(5),
                    ]),

                Infolists\Components\Section::make('Delivery Address')
                    ->schema([
                        Infolists\Components\TextEntry::make('deliveryDetail.full_name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('deliveryDetail.phone')
                            ->label('Phone'),
                        Infolists\Components\TextEntry::make('deliveryDetail.city')
                            ->label('City'),
                        Infolists\Components\TextEntry::make('deliveryDetail.region')
                            ->label('Region'),
                        Infolists\Components\TextEntry::make('deliveryDetail.country')
                            ->label('Country'),
                        Infolists\Components\TextEntry::make('deliveryDetail.digital_address')
                            ->label('Digital Address')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('deliveryDetail.delivery_notes')
                            ->label('Delivery Notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Status History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('statusHistory')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('notes')
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('When')
                                    ->dateTime(),
                            ])
                            ->columns(3),
                    ]),

                Infolists\Components\Section::make('Customer Rating')
                    ->schema([
                        Infolists\Components\TextEntry::make('rating.rating')
                            ->label('Rating')
                            ->badge()
                            ->color(fn (?int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state === 3 => 'warning',
                                default => 'danger',
                            })
                            ->formatStateUsing(fn (?int $state): string => $state
                                ? str_repeat('★', $state).str_repeat('☆', 5 - $state)
                                : '—'),
                        Infolists\Components\TextEntry::make('rating.comment')
                            ->label('Comment')
                            ->placeholder('No comment left'),
                        Infolists\Components\TextEntry::make('rating.created_at')
                            ->label('Rated At')
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->visible(fn (EcommerceOrder $record): bool => $record->rating !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_ghs')
                    ->label('Total (GHS)')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(EcommerceOrderStatus::class),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options(EcommerceOrderPaymentStatus::class),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn (EcommerceOrder $record) => $record->status === EcommerceOrderStatus::Pending)
                        ->form([
                            Forms\Components\TextInput::make('shipping_fee_ghs')
                                ->label('Shipping Fee (GHS)')
                                ->numeric()
                                ->prefix('GH₵')
                                ->default(0)
                                ->required(),
                        ])
                        ->action(function (EcommerceOrder $record, array $data): void {
                            $shippingFee = (float) $data['shipping_fee_ghs'];
                            $rateLog = ExchangeRateLog::latest('created_at')->first();
                            $rate = $rateLog?->rate ?? 1;
                            $newTotal = $record->subtotal_ghs + $shippingFee - $record->discount_ghs;

                            $record->update([
                                'status' => EcommerceOrderStatus::AwaitingPayment->value,
                                'shipping_fee_ghs' => $shippingFee,
                                'total_ghs' => $newTotal,
                                'total_usd' => $rate > 0 ? round($newTotal / $rate, 2) : null,
                                'approved_by' => auth()->id(),
                            ]);

                            self::appendHistory($record, EcommerceOrderStatus::AwaitingPayment->value, 'Approved by staff. Awaiting customer payment.');

                            $user = $record->user;
                            $user->notify(new EcommerceOrderApproved($record));
                            app(PushNotificationService::class)->sendToUser(
                                $user->id,
                                'Order Approved',
                                "Your order {$record->order_number} is approved. Please pay GHS {$record->total_ghs}.",
                                ['type' => 'ecommerce_order', 'order_id' => $record->id]
                            );
                        })
                        ->successNotificationTitle('Order approved — customer notified to pay'),

                    Tables\Actions\Action::make('process')
                        ->label('Mark as Processing')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->color('info')
                        ->visible(fn (EcommerceOrder $record) => $record->status === EcommerceOrderStatus::Paid)
                        ->requiresConfirmation()
                        ->action(function (EcommerceOrder $record): void {
                            $record->update(['status' => EcommerceOrderStatus::Processing->value]);
                            self::appendHistory($record, EcommerceOrderStatus::Processing->value, 'Order is being processed.');

                            $user = $record->user;
                            $user->notify(new EcommerceOrderProcessing($record));
                            app(PushNotificationService::class)->sendToUser(
                                $user->id,
                                'Order Being Processed',
                                "Order {$record->order_number} is being processed.",
                                ['type' => 'ecommerce_order', 'order_id' => $record->id]
                            );
                        })
                        ->successNotificationTitle('Order moved to processing'),

                    Tables\Actions\Action::make('pack')
                        ->label('Mark as Packed')
                        ->icon('heroicon-m-archive-box')
                        ->color('info')
                        ->visible(fn (EcommerceOrder $record) => $record->status === EcommerceOrderStatus::Processing)
                        ->requiresConfirmation()
                        ->action(function (EcommerceOrder $record): void {
                            $record->update(['status' => EcommerceOrderStatus::Packed->value]);
                            self::appendHistory($record, EcommerceOrderStatus::Packed->value, 'Order has been packed.');

                            $user = $record->user;
                            $user->notify(new EcommerceOrderPacked($record));
                            app(PushNotificationService::class)->sendToUser(
                                $user->id,
                                'Order Packed',
                                "Order {$record->order_number} has been packed.",
                                ['type' => 'ecommerce_order', 'order_id' => $record->id]
                            );
                        })
                        ->successNotificationTitle('Order marked as packed'),

                    Tables\Actions\Action::make('dispatch')
                        ->label('Dispatch')
                        ->icon('heroicon-m-truck')
                        ->color('primary')
                        ->visible(fn (EcommerceOrder $record) => $record->status === EcommerceOrderStatus::Packed)
                        ->form([
                            Forms\Components\TextInput::make('tracking_number')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('courier')
                                ->nullable()
                                ->maxLength(100),
                        ])
                        ->action(function (EcommerceOrder $record, array $data): void {
                            $shipment = $record->shipment;

                            if (! $shipment) {
                                EcommerceOrderShipment::create([
                                    'order_id' => $record->id,
                                    'tracking_number' => $data['tracking_number'],
                                    'courier' => $data['courier'] ?? null,
                                    'status' => 'assigned',
                                    'dispatched_at' => now(),
                                ]);
                            } else {
                                $shipment->update([
                                    'tracking_number' => $data['tracking_number'],
                                    'courier' => $data['courier'] ?? null,
                                    'dispatched_at' => now(),
                                ]);
                            }

                            $record->update(['status' => EcommerceOrderStatus::Dispatched->value]);
                            self::appendHistory($record, EcommerceOrderStatus::Dispatched->value, 'Order dispatched.');

                            $user = $record->user;
                            $user->notify(new EcommerceOrderDispatched($record));
                            app(PushNotificationService::class)->sendToUser(
                                $user->id,
                                'Order Dispatched',
                                "Order {$record->order_number} is on its way! Tracking: {$data['tracking_number']}",
                                ['type' => 'ecommerce_order', 'order_id' => $record->id]
                            );
                        })
                        ->successNotificationTitle('Order dispatched'),

                    Tables\Actions\Action::make('deliver')
                        ->label('Mark as Delivered')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->visible(fn (EcommerceOrder $record) => in_array($record->status, [EcommerceOrderStatus::Dispatched, EcommerceOrderStatus::InTransit]))
                        ->requiresConfirmation()
                        ->action(function (EcommerceOrder $record): void {
                            $record->update(['status' => EcommerceOrderStatus::Delivered->value]);

                            if ($record->shipment) {
                                $record->shipment->update(['delivered_at' => now(), 'status' => 'delivered']);
                            }

                            self::appendHistory($record, EcommerceOrderStatus::Delivered->value, 'Order delivered to customer.');

                            $user = $record->user;
                            $user->notify(new EcommerceOrderDelivered($record));
                            app(PushNotificationService::class)->sendToUser(
                                $user->id,
                                'Order Delivered',
                                "Order {$record->order_number} has been delivered!",
                                ['type' => 'ecommerce_order', 'order_id' => $record->id]
                            );
                        })
                        ->successNotificationTitle('Order marked as delivered'),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel Order')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn (EcommerceOrder $record) => ! in_array($record->status, [EcommerceOrderStatus::Delivered, EcommerceOrderStatus::Refunded]))
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Cancellation Reason')
                                ->nullable()
                                ->rows(3),
                        ])
                        ->action(function (EcommerceOrder $record, array $data): void {
                            $stockRestorableStatuses = [
                                EcommerceOrderStatus::Pending, EcommerceOrderStatus::AwaitingPayment,
                                EcommerceOrderStatus::Paid, EcommerceOrderStatus::Processing, EcommerceOrderStatus::Packed,
                            ];

                            if (in_array($record->status, $stockRestorableStatuses)) {
                                $record->load('items');
                                app(EcommerceInventoryService::class)->restoreForOrder($record, auth()->user());
                            }

                            $record->update([
                                'status' => EcommerceOrderStatus::Cancelled->value,
                                'cancelled_reason' => $data['reason'] ?? null,
                                'cancelled_by' => auth()->id(),
                            ]);

                            self::appendHistory($record, EcommerceOrderStatus::Cancelled->value, 'Cancelled by staff. '.($data['reason'] ?? ''));
                        })
                        ->successNotificationTitle('Order cancelled'),

                    Tables\Actions\Action::make('refund')
                        ->label('Refund')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('warning')
                        ->visible(fn (EcommerceOrder $record) => in_array($record->status, [EcommerceOrderStatus::Paid, EcommerceOrderStatus::Delivered]))
                        ->requiresConfirmation()
                        ->modalDescription('This will mark the order as refunded. Process the payment reversal manually in your payment gateway dashboard.')
                        ->action(function (EcommerceOrder $record): void {
                            $record->update([
                                'status' => EcommerceOrderStatus::Refunded->value,
                                'payment_status' => EcommerceOrderPaymentStatus::Refunded->value,
                            ]);

                            self::appendHistory($record, EcommerceOrderStatus::Refunded->value, 'Order refunded by staff.');
                        })
                        ->successNotificationTitle('Order marked as refunded'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function appendHistory(EcommerceOrder $order, string $status, string $notes = ''): void
    {
        EcommerceOrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEcommerceOrders::route('/'),
            'view' => Pages\ViewEcommerceOrder::route('/{record}'),
        ];
    }
}

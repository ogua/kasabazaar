<?php

namespace App\Filament\Resources\EcommerceOrderResource\Pages;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Filament\Resources\EcommerceOrderResource;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderShipment;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\ExchangeRateLog;
use App\Notifications\Ecommerce\EcommerceOrderApproved;
use App\Notifications\Ecommerce\EcommerceOrderCancelled;
use App\Notifications\Ecommerce\EcommerceOrderDelivered;
use App\Notifications\Ecommerce\EcommerceOrderDispatched;
use App\Notifications\Ecommerce\EcommerceOrderPacked;
use App\Notifications\Ecommerce\EcommerceOrderProcessing;
use App\Notifications\Ecommerce\EcommerceOrderRefunded;
use App\Services\EcommerceInventoryService;
use App\Services\PushNotificationService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewEcommerceOrder extends ViewRecord
{
    protected static string $resource = EcommerceOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === EcommerceOrderStatus::Pending)
                ->form([
                    Forms\Components\TextInput::make('shipping_fee_ghs')
                        ->label('Shipping Fee (GHS)')
                        ->numeric()
                        ->prefix('GH₵')
                        ->default(0)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $record = $this->record;
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

                    $this->appendHistory($record, EcommerceOrderStatus::AwaitingPayment->value, 'Approved by staff. Awaiting customer payment.');

                    $user = $record->user;
                    $user->notify(new EcommerceOrderApproved($record));
                    app(PushNotificationService::class)->sendToUser(
                        $user->id, 'Order Approved',
                        "Your order {$record->order_number} is approved. Please pay GHS {$record->total_ghs}.",
                        ['type' => 'ecommerce_order', 'order_id' => $record->id]
                    );

                    $this->refreshFormData(['status', 'shipping_fee_ghs', 'total_ghs']);
                })
                ->successNotificationTitle('Order approved'),

            Actions\Action::make('process')
                ->label('Mark Processing')
                ->icon('heroicon-m-cog-6-tooth')
                ->color('info')
                ->visible(fn () => $this->record->status === EcommerceOrderStatus::Paid)
                ->requiresConfirmation()
                ->action(function (): void {
                    $record = $this->record;
                    $record->update(['status' => EcommerceOrderStatus::Processing->value]);
                    $this->appendHistory($record, EcommerceOrderStatus::Processing->value, 'Order is being processed.');

                    $user = $record->user;
                    $user->notify(new EcommerceOrderProcessing($record));
                    app(PushNotificationService::class)->sendToUser(
                        $user->id, 'Order Being Processed',
                        "Order {$record->order_number} is being processed.",
                        ['type' => 'ecommerce_order', 'order_id' => $record->id]
                    );

                    $this->refreshFormData(['status']);
                })
                ->successNotificationTitle('Order moved to processing'),

            Actions\Action::make('pack')
                ->label('Mark Packed')
                ->icon('heroicon-m-archive-box')
                ->color('info')
                ->visible(fn () => $this->record->status === EcommerceOrderStatus::Processing)
                ->requiresConfirmation()
                ->action(function (): void {
                    $record = $this->record;
                    $record->update(['status' => EcommerceOrderStatus::Packed->value]);
                    $this->appendHistory($record, EcommerceOrderStatus::Packed->value, 'Order has been packed.');

                    $user = $record->user;
                    $user->notify(new EcommerceOrderPacked($record));
                    app(PushNotificationService::class)->sendToUser(
                        $user->id, 'Order Packed',
                        "Order {$record->order_number} has been packed.",
                        ['type' => 'ecommerce_order', 'order_id' => $record->id]
                    );

                    $this->refreshFormData(['status']);
                })
                ->successNotificationTitle('Order marked as packed'),

            Actions\Action::make('dispatch')
                ->label('Dispatch')
                ->icon('heroicon-m-truck')
                ->color('primary')
                ->visible(fn () => $this->record->status === EcommerceOrderStatus::Packed)
                ->form([
                    Forms\Components\TextInput::make('tracking_number')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('courier')
                        ->nullable()
                        ->maxLength(100),
                ])
                ->action(function (array $data): void {
                    $record = $this->record;
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
                    $this->appendHistory($record, EcommerceOrderStatus::Dispatched->value, 'Order dispatched.');

                    $user = $record->user;
                    $user->notify(new EcommerceOrderDispatched($record));
                    app(PushNotificationService::class)->sendToUser(
                        $user->id, 'Order Dispatched',
                        "Order {$record->order_number} is on its way! Tracking: {$data['tracking_number']}",
                        ['type' => 'ecommerce_order', 'order_id' => $record->id]
                    );

                    $this->refreshFormData(['status']);
                })
                ->successNotificationTitle('Order dispatched'),

            Actions\Action::make('deliver')
                ->label('Mark Delivered')
                ->icon('heroicon-m-check-badge')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, [EcommerceOrderStatus::Dispatched, EcommerceOrderStatus::InTransit]))
                ->requiresConfirmation()
                ->action(function (): void {
                    $record = $this->record;
                    $record->update(['status' => EcommerceOrderStatus::Delivered->value]);

                    if ($record->shipment) {
                        $record->shipment->update(['delivered_at' => now(), 'status' => 'delivered']);
                    }

                    $this->appendHistory($record, EcommerceOrderStatus::Delivered->value, 'Order delivered to customer.');

                    $user = $record->user;
                    $user->notify(new EcommerceOrderDelivered($record));
                    app(PushNotificationService::class)->sendToUser(
                        $user->id, 'Order Delivered',
                        "Order {$record->order_number} has been delivered!",
                        ['type' => 'ecommerce_order', 'order_id' => $record->id]
                    );

                    $this->refreshFormData(['status']);
                })
                ->successNotificationTitle('Order marked as delivered'),

            Actions\Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn () => ! in_array($this->record->status, [EcommerceOrderStatus::Delivered, EcommerceOrderStatus::Refunded]))
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Cancellation Reason')
                        ->nullable()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $record = $this->record;
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

                    $this->appendHistory($record, EcommerceOrderStatus::Cancelled->value, 'Cancelled by staff. '.($data['reason'] ?? ''));
                    $record->user->notify(new EcommerceOrderCancelled($record));
                    $this->refreshFormData(['status', 'cancelled_reason']);
                })
                ->successNotificationTitle('Order cancelled'),

            Actions\Action::make('refund')
                ->label('Refund')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () => in_array($this->record->status, [EcommerceOrderStatus::Paid, EcommerceOrderStatus::Delivered]))
                ->requiresConfirmation()
                ->modalDescription('This will mark the order as refunded. Process the payment reversal manually in your payment gateway dashboard.')
                ->action(function (): void {
                    $record = $this->record;
                    $record->update([
                        'status' => EcommerceOrderStatus::Refunded->value,
                        'payment_status' => EcommerceOrderPaymentStatus::Refunded->value,
                    ]);

                    $this->appendHistory($record, EcommerceOrderStatus::Refunded->value, 'Order refunded by staff.');
                    $record->user->notify(new EcommerceOrderRefunded($record));
                    $this->refreshFormData(['status', 'payment_status']);
                })
                ->successNotificationTitle('Order marked as refunded'),
        ];
    }

    protected function resolveRecord(int|string $key): EcommerceOrder
    {
        return EcommerceOrder::with(['user', 'items', 'deliveryDetail', 'statusHistory', 'shipment'])->findOrFail($key);
    }

    private function appendHistory(EcommerceOrder $order, string $status, string $notes = ''): void
    {
        EcommerceOrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }
}

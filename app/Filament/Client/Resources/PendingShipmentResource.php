<?php

namespace App\Filament\Client\Resources;

use Filament\Tables;
use App\Models\Shipment;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Client\Resources\PendingShipmentResource\Pages;

class PendingShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Pending';

    protected static ?string $slug = 'pending-shipments';

    protected static ?string $modelLabel = 'Pending Shipment';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', 'pending')
            ->where('client_id', auth()->user()->client_id)
            ->orderBy('created_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')
            ->where('client_id', auth()->user()->client_id)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending')
            ->where('client_id', auth()->user()->client_id)
            ->count() > 0
            ? 'warning'
            : 'primary';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->searchable()
                    ->badge(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        default   => 'danger',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Shipping Status')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('tracking_number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('shipping_reference')
                    ->label('Reference')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->copyable(),

                Tables\Columns\TextColumn::make('origin_branch_id')
                    ->label('Shipping From')
                    ->icon('heroicon-m-arrow-up')
                    ->iconColor('danger')
                    ->searchable(),

                Tables\Columns\TextColumn::make('destination_branch_id')
                    ->label('Shipping To')
                    ->icon('heroicon-m-arrow-down')
                    ->iconColor('warning')
                    ->searchable(),

                Tables\Columns\TextColumn::make('shipped_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shipping_cost')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('danger')
                    ->state(fn($record) => number_format($record->shipping_cost, 2))
                    ->prefix('$'),

                Tables\Columns\TextColumn::make('total')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->state(fn($record) => number_format($record->total, 2))
                    ->prefix('$'),

                Tables\Columns\TextColumn::make('paid')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->state(fn($record) => number_format($record->paid, 2))
                    ->prefix('$'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid'    => 'Paid',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make('pay')
                        ->label('Pay Now')
                        ->icon('heroicon-m-banknotes')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->url(fn($record) => route('make-payment', ['record' => $record]), shouldOpenInNewTab: true),

                    Tables\Actions\Action::make('receiver')
                        ->label('Receiver Information')
                        ->icon('heroicon-m-user')
                        ->fillForm(function ($record) {
                            return [
                                'receivers' => $record->receivers,
                            ];
                        })
                        ->infolist([
                            RepeatableEntry::make('receivers')
                                ->label('')
                                ->schema([
                                    ImageEntry::make('receiver_name')->label(''),
                                    TextEntry::make('receiver_phone'),
                                    TextEntry::make('receiver_email'),
                                    TextEntry::make('country')->state(fn($record) => "{$record->mcountry?->name}, {$record->mstate?->name}, {$record->mcity?->name}"),
                                    TextEntry::make('address'),
                                    TextEntry::make('receiver_id_type'),
                                    TextEntry::make('receiver_id_number'),
                                    TextEntry::make('item_cost'),
                                ])
                                ->columns(4),
                        ])
                        ->modalSubmitAction(false)
                        ->modalHeading('Receiver Information'),

                    Tables\Actions\Action::make('items')
                        ->label('Shipping Items')
                        ->color('info')
                        ->icon('heroicon-m-cube')
                        ->fillForm(function ($record) {
                            return [
                                'items' => $record->items,
                            ];
                        })
                        ->infolist([
                            RepeatableEntry::make('items')
                                ->label('')
                                ->schema([
                                    ImageEntry::make('product.product_image')->label('')->columnSpan(2),
                                    TextEntry::make('receiver.receiver_name')->badge(),
                                    TextEntry::make('box_no'),
                                    TextEntry::make('product.name')
                                        ->state(fn($record) => $record->product?->name . ' (' . $record->quantity . 'x)')
                                        ->columnSpan(2),
                                    TextEntry::make('item_cost')->label('Value'),
                                ])
                                ->columns(7),
                        ])
                        ->modalSubmitAction(false),

                    Tables\Actions\Action::make('Print Invoice')
                        ->icon('heroicon-m-receipt-percent')
                        ->color('success')
                        ->url(fn($record) => route('shipping-invoice', $record->id), shouldOpenInNewTab: true),

                    Tables\Actions\Action::make('payments')
                        ->label('View Payments')
                        ->color('warning')
                        ->icon('heroicon-m-banknotes')
                        ->fillForm(function ($record) {
                            return [
                                'payments' => $record->payments,
                            ];
                        })
                        ->infolist([
                            RepeatableEntry::make('payments')
                                ->label('')
                                ->schema([
                                    TextEntry::make('shipment.client.name')->label('Client'),
                                    TextEntry::make('payment_ref'),
                                    TextEntry::make('paying_method'),
                                    TextEntry::make('amount')->prefix('$'),
                                    TextEntry::make('paid_on'),
                                    TextEntry::make('enteredBy.name')->label('Recorded By'),
                                ])
                                ->columns(6),
                        ])
                        ->modalHeading('Payment Information')
                        ->modalSubmitAction(false),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPendingShipments::route('/'),
            'create' => Pages\CreatePendingShipment::route('/create'),
            'edit'   => Pages\EditPendingShipment::route('/{record}/edit'),
        ];
    }
}

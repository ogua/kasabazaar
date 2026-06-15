<?php

namespace App\Filament\Resources;

use App\Enums\EcommerceOrderShipmentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Filament\Resources\EcommerceShipmentResource\Pages;
use App\Models\EcommerceOrderShipment;
use App\Models\EcommerceShipmentTrackingLog;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EcommerceShipmentResource extends Resource
{
    protected static ?string $model = EcommerceOrderShipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 5;

    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('order', fn (Builder $q) => $q->where('branch_id', Filament::getTenant()?->id));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Shipment Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label('Order Number')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('tracking_number')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('courier')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('estimated_delivery')
                            ->label('Est. Delivery')
                            ->date()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('dispatched_at')
                            ->dateTime()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('delivered_at')
                            ->dateTime()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Tracking History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('trackingLogs')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('recorded_at')
                                    ->label('Time')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('status')
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('latitude')
                                    ->label('Lat'),
                                Infolists\Components\TextEntry::make('longitude')
                                    ->label('Lng'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tracking_number')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('courier')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_delivery')
                    ->label('Est. Delivery')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dispatched_at')
                    ->label('Dispatched')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('Delivered')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(EcommerceOrderShipmentStatus::class),
                Tables\Filters\Filter::make('dispatched_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dispatched From'),
                        Forms\Components\DatePicker::make('until')->label('Dispatched Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('dispatched_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('dispatched_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('update_shipment')
                    ->label('Update')
                    ->icon('heroicon-m-pencil')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('tracking_number')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('courier')
                            ->nullable()
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('estimated_delivery')
                            ->nullable(),
                        Forms\Components\Select::make('status')
                            ->options(EcommerceOrderShipmentStatus::class)
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('notes')
                            ->nullable()
                            ->rows(2),
                    ])
                    ->fillForm(fn (EcommerceOrderShipment $record): array => [
                        'tracking_number' => $record->tracking_number,
                        'courier' => $record->courier,
                        'estimated_delivery' => $record->estimated_delivery?->toDateString(),
                        'status' => $record->status->value,
                        'notes' => $record->notes,
                    ])
                    ->action(function (EcommerceOrderShipment $record, array $data): void {
                        $record->update([
                            'tracking_number' => $data['tracking_number'],
                            'courier' => $data['courier'] ?? null,
                            'estimated_delivery' => $data['estimated_delivery'] ?? null,
                            'status' => $data['status'],
                            'notes' => $data['notes'] ?? null,
                        ]);
                    })
                    ->successNotificationTitle('Shipment updated'),
                Tables\Actions\Action::make('add_tracking')
                    ->label('Add Tracking')
                    ->icon('heroicon-m-map-pin')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('latitude')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('longitude')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('status')
                            ->nullable()
                            ->maxLength(100),
                        Forms\Components\DateTimePicker::make('recorded_at')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (EcommerceOrderShipment $record, array $data): void {
                        EcommerceShipmentTrackingLog::create([
                            'order_shipment_id' => $record->id,
                            'latitude' => $data['latitude'],
                            'longitude' => $data['longitude'],
                            'status' => $data['status'] ?? null,
                            'recorded_at' => $data['recorded_at'],
                        ]);

                        if (
                            ! empty($data['status']) &&
                            str_contains(strtolower((string) $data['status']), 'transit') &&
                            $record->order->status !== EcommerceOrderStatus::InTransit
                        ) {
                            $record->update(['status' => EcommerceOrderShipmentStatus::InTransit]);
                            $record->order->update(['status' => EcommerceOrderStatus::InTransit]);
                            $record->order->statusHistory()->create([
                                'status' => EcommerceOrderStatus::InTransit->value,
                                'notes' => 'Order is now in transit.',
                                'created_by' => auth()->id(),
                            ]);
                        }
                    })
                    ->successNotificationTitle('Tracking point added'),
            ])
            ->bulkActions([])
            ->defaultSort('dispatched_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEcommerceShipments::route('/'),
            'view' => Pages\ViewEcommerceShipment::route('/{record}'),
        ];
    }
}

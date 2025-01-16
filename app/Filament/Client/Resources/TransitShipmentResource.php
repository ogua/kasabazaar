<?php

namespace App\Filament\Client\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Shipment;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TransitShipment;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Client\Resources\TransitShipmentResource\Pages;
use App\Filament\Client\Resources\TransitShipmentResource\RelationManagers;

class TransitShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Transit Shipments';

    protected static ?string $slug = 'in-transit-shipments';

    protected static ?string $modelLabel = 'Transit Shipments';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status','in transit')
            ->where('client_id', auth()->user()->client_id)
            ->orderBy('created_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status','in transit')
        ->where('client_id', auth()->user()->client_id)
        ->count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')->searchable()->badge(),

                Tables\Columns\TextColumn::make('payment_status')->label('Payment Status')->badge()->color('success'),

                Tables\Columns\TextColumn::make('status')->label('Shipping Status')->badge(),

                Tables\Columns\TextColumn::make('tracking_number')->searchable(),
                Tables\Columns\TextColumn::make('origin_branch_id')->label('Shipping From')->icon('heroicon-m-arrow-up')->iconColor('danger')->searchable(),

                Tables\Columns\TextColumn::make('destination_branch_id')->label('Shipping To')->icon('heroicon-m-arrow-down')->iconColor('warning')->searchable(),

                Tables\Columns\TextColumn::make('shipped_at')->dateTime()->sortable(),

                Tables\Columns\TextColumn::make('shipping_cost')->numeric()->sortable()->badge()->color('danger')->state(fn($record) => number_format($record->shipping_cost, 2))->prefix('$'),

                Tables\Columns\TextColumn::make('total')->sortable()->badge()->color('info')->state(fn($record) => number_format($record->total, 2))->prefix('$'),

                Tables\Columns\TextColumn::make('paid')->sortable()->badge()->color('warning')->state(fn($record) => number_format($record->paid, 2))->prefix('$'),

                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListTransitShipments::route('/'),
            'create' => Pages\CreateTransitShipment::route('/create'),
            'edit' => Pages\EditTransitShipment::route('/{record}/edit'),
        ];
    }
}

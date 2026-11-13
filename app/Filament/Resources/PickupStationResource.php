<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PickupStationResource\Pages;
use App\Models\PickupStation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * KASAROSE pickup stations and warehouses in Ghana and the USA. Customers collect
 * Pickup orders from a pickup point; vendors deliver finished orders to a warehouse
 * for inspection, and customer returns come to one too.
 */
class PickupStationResource extends Resource
{
    protected static ?string $model = PickupStation::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationLabel = 'Pickup Stations & Warehouses';

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(120),
            Forms\Components\Select::make('country')
                ->options(['Ghana' => 'Ghana', 'USA' => 'USA'])
                ->required()
                ->native(false),
            Forms\Components\TextInput::make('region')->label('Region / State')->maxLength(100),
            Forms\Components\TextInput::make('city')->required()->maxLength(100),
            Forms\Components\TextInput::make('address')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\TextInput::make('phone')->tel()->maxLength(40),
            Forms\Components\TextInput::make('opening_hours')->placeholder('Mon-Sat, 9am-6pm')->maxLength(255),
            Forms\Components\Textarea::make('instructions')->label('Instructions for customers and vendors')->rows(2)->columnSpanFull(),
            Forms\Components\Toggle::make('is_pickup_point')->label('Customers can collect orders here')->default(true),
            Forms\Components\Toggle::make('is_warehouse')->label('Warehouse: vendors deliver here and returns arrive here'),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('country')->badge(),
                Tables\Columns\TextColumn::make('city')->searchable(),
                Tables\Columns\IconColumn::make('is_pickup_point')->boolean()->label('Pickup'),
                Tables\Columns\IconColumn::make('is_warehouse')->boolean()->label('Warehouse'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country')->options(['Ghana' => 'Ghana', 'USA' => 'USA']),
            ])
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePickupStations::route('/'),
        ];
    }
}

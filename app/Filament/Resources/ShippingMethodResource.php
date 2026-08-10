<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingMethodResource\Pages;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 11;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shipping Method')
                    ->description('Vendor-specific methods override the marketplace default for that vendor. Leave zone empty to apply to all zones.')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\Select::make('shipping_zone_id')
                            ->label('Zone (empty = all zones)')
                            ->options(fn () => ShippingZone::pluck('name', 'id'))
                            ->nullable(),
                        Forms\Components\TextInput::make('fee_ghs')->numeric()->required(),
                        Forms\Components\TextInput::make('min_days')->numeric()->nullable(),
                        Forms\Components\TextInput::make('max_days')->numeric()->nullable(),
                        Forms\Components\TextInput::make('free_shipping_threshold_ghs')->numeric()->nullable(),
                        Forms\Components\Toggle::make('is_active')->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('zone.name')->placeholder('All zones'),
                Tables\Columns\TextColumn::make('fee_ghs')->money('GHS'),
                Tables\Columns\TextColumn::make('vendor.business_name')->placeholder('Marketplace default'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit' => Pages\EditShippingMethod::route('/{record}/edit'),
        ];
    }
}

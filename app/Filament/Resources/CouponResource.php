<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 9;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Coupon')
                    ->schema([
                        Forms\Components\TextInput::make('code')->required()->maxLength(50)->unique(ignoreRecord: true)->alphaDash(),
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor (leave empty for marketplace-wide)')
                            ->options(fn () => Vendor::pluck('business_name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('type')
                            ->options(['percentage' => 'Percentage', 'fixed' => 'Fixed Amount (GHS)'])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('value')
                            ->numeric()
                            ->required()
                            ->suffix(fn (Forms\Get $get) => $get('type') === 'percentage' ? '%' : 'GHS'),
                        Forms\Components\TextInput::make('min_order_amount_ghs')->numeric()->nullable(),
                        Forms\Components\TextInput::make('max_discount_ghs')->numeric()->nullable(),
                        Forms\Components\TextInput::make('usage_limit')->numeric()->nullable(),
                        Forms\Components\TextInput::make('usage_limit_per_user')->numeric()->nullable(),
                        Forms\Components\DateTimePicker::make('starts_at')->nullable(),
                        Forms\Components\DateTimePicker::make('expires_at')->nullable(),
                        Forms\Components\Toggle::make('is_active')->default(true),
                        Forms\Components\Hidden::make('created_by')->default(fn () => auth()->id()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('vendor.business_name')->placeholder('Marketplace-wide')->badge(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('value'),
                Tables\Columns\TextColumn::make('used_count')->label('Used'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->placeholder('Never'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')->options(['1' => 'Active', '0' => 'Inactive']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}

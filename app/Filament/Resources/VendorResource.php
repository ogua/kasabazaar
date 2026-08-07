<?php

namespace App\Filament\Resources;

use App\Enums\VendorStatus;
use App\Filament\Resources\VendorResource\Pages;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 8;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vendor')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                        Forms\Components\Select::make('status')
                            ->options(VendorStatus::class)
                            ->required(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        Forms\Components\TextInput::make('phone')->maxLength(30),
                        Forms\Components\TextInput::make('support_email')->email()->maxLength(255),
                        Forms\Components\Textarea::make('description')->columnSpanFull()->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(asset('images/kasabazaar-logo.png')),
                Tables\Columns\TextColumn::make('business_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.email')->label('Owner Email')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('commission_rate')->suffix('%'),
                Tables\Columns\TextColumn::make('wallet.balance_ghs')->label('Balance (GHS)')->money('GHS'),
                Tables\Columns\TextColumn::make('products_count')->counts('products')->label('Products'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(VendorStatus::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-m-no-symbol')
                    ->color('danger')
                    ->visible(fn (Vendor $record) => $record->status === VendorStatus::Active)
                    ->requiresConfirmation()
                    ->action(function (Vendor $record) {
                        $record->update(['status' => VendorStatus::Suspended->value]);
                        Notification::make()->title('Vendor suspended')->success()->send();
                    }),
                Tables\Actions\Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Vendor $record) => $record->status === VendorStatus::Suspended)
                    ->action(function (Vendor $record) {
                        $record->update(['status' => VendorStatus::Active->value]);
                        Notification::make()->title('Vendor reactivated')->success()->send();
                    }),
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
            'index' => Pages\ListVendors::route('/'),
            'view' => Pages\ViewVendor::route('/{record}'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}

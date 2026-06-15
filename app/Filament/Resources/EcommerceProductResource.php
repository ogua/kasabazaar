<?php

namespace App\Filament\Resources;

use App\Enums\EcommerceInventoryLogType;
use App\Filament\Resources\EcommerceProductResource\Pages;
use App\Models\EcommerceProduct;
use App\Services\EcommerceInventoryService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EcommerceProductResource extends Resource
{
    protected static ?string $model = EcommerceProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 2;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Info')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(100)
                            ->nullable(),
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('branch_id')
                            ->default(fn () => Filament::getTenant()?->id),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price_ghs')
                            ->label('Price (GHS)')
                            ->numeric()
                            ->required()
                            ->prefix('GH₵')
                            ->default(0),
                        Forms\Components\TextInput::make('price_usd')
                            ->label('Price (USD)')
                            ->numeric()
                            ->nullable()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('discount_price_ghs')
                            ->label('Discount Price (GHS)')
                            ->numeric()
                            ->nullable()
                            ->prefix('GH₵'),
                        Forms\Components\TextInput::make('discount_price_usd')
                            ->label('Discount Price (USD)')
                            ->numeric()
                            ->nullable()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('weight')
                            ->numeric()
                            ->nullable()
                            ->suffix('kg'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Stock & Visibility')
                    ->schema([
                        Forms\Components\TextInput::make('stock')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('low_stock_threshold')
                            ->label('Low Stock Alert At')
                            ->numeric()
                            ->default(5)
                            ->minValue(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active / Visible to customers')
                            ->default(true),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Product Images')
                    ->schema([
                        Forms\Components\Repeater::make('images')
                            ->relationship('images')
                            ->label('')
                            ->schema([
                                Forms\Components\FileUpload::make('path')
                                    ->label('Image')
                                    ->image()
                                    ->directory('ecommerce-product-images')
                                    ->required(),
                                Forms\Components\Toggle::make('is_primary')
                                    ->label('Primary Image')
                                    ->default(false),
                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(3)
                            ->addActionLabel('+ Add Image')
                            ->reorderableWithButtons()
                            ->collapsible(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('primary_image')
                    ->label('Image')
                    ->getStateUsing(fn (EcommerceProduct $record) => $record->images->first()?->path)
                    ->disk('public')
                    ->defaultImageUrl(asset('images/kasabazaar-logo.png')),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('price_ghs')
                    ->label('Price (GHS)')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->badge()
                    ->color(fn (EcommerceProduct $record): string => match (true) {
                        $record->stock === 0 => 'danger',
                        $record->stock <= $record->low_stock_threshold => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\ToggleColumn::make('is_featured')
                    ->label('Featured'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn (Builder $query) => $query->whereColumn('stock', '<=', 'low_stock_threshold')->where('stock', '>', 0)),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) => $query->where('stock', 0)),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-m-arrows-up-down')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->label('Adjustment Type')
                            ->options(EcommerceInventoryLogType::class)
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->label('Quantity'),
                        Forms\Components\TextInput::make('reason')
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->action(function (EcommerceProduct $record, array $data): void {
                        $type = EcommerceInventoryLogType::from($data['type']);
                        $change = $type === EcommerceInventoryLogType::Decrease ? -abs((int) $data['quantity']) : abs((int) $data['quantity']);

                        app(EcommerceInventoryService::class)->adjust(
                            $record,
                            $change,
                            $type,
                            auth()->user(),
                            $data['reason'] ?? null
                        );
                    })
                    ->successNotificationTitle('Stock adjusted successfully'),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withTrashed());
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEcommerceProducts::route('/'),
            'create' => Pages\CreateEcommerceProduct::route('/create'),
            'edit' => Pages\EditEcommerceProduct::route('/{record}/edit'),
        ];
    }
}

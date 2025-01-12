<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    //protected static ?int $sort = 2;

    protected static bool $isScopedToTenant = false;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                ->description('')
                ->schema([
                Forms\Components\FileUpload::make('product_image')
                    ->image()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

                // Forms\Components\TextInput::make('sku')
                //     ->label('SKU')
                //     ->required()
                //     ->maxLength(255),

                Forms\Components\Select::make('category')
                    ->required()
                    ->options([
                        'Vehicles' => 'Vehicles',
                        'Home Appliances' => 'Home Appliances',
                        'Electronic Gadgets' => 'Electronic Gadgets',
                        'Construction/Building Materials' => 'Construction/Building Materials',
                        'Others' => 'Others',
                    ])
                    ->searchable(),

                Forms\Components\TextInput::make('weight')
                    ->numeric()
                    ->default(null),

                Forms\Components\TextInput::make('value')
                    ->required()
                    ->numeric(),

                // Forms\Components\TextInput::make('branch_id')
                //     ->required()
                //     ->maxLength(36),

                ])
                ->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('product_image')
                ->defaultImageUrl(url('/images/no-image.png')),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->badge(),

                // Tables\Columns\TextColumn::make('sku')
                //     ->label('SKU')
                //     ->searchable(),

                Tables\Columns\TextColumn::make('category')
                    ->searchable(),

                Tables\Columns\TextColumn::make('weight')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->numeric()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('branch_id')
                //     ->searchable(),

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
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use App\Models\Product;
use Filament\Forms\Form;
use App\Models\Quotation;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use App\Filament\Resources\QuotationResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\QuotationResource\RelationManagers;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Section::make('')
            ->description('')
            ->schema([
                Forms\Components\Select::make('client_id')
                ->label('Client')
                ->required()
                ->options(Client::get()->pluck('fullname_branch','id'))
                ->preload()
                ->searchable(),

                Forms\Components\TextInput::make('shipping_cost')
                ->numeric()
                ->prefix('$')
                ->live(onBlur: true)
                ->default(0),

                Forms\Components\Section::make('Shipping')
                ->description('Quotation Items')
                ->schema([

                    TableRepeater::make('items')
                    ->label('')
                    ->live()
                    ->relationship()
                    ->colStyles([
                        'box_no' => 'width: 200px;',
                        'quantity' => 'width: 100px;',
                        'item_cost' => 'width: 200px;',
                        ])
                        ->schema([
                            Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->required()
                            ->options(Product::pluck('name','id'))
                            ->preload()
                            ->searchable()
                            ->live(),

                            Forms\Components\TextInput::make('quantity')
                            ->label('Qty')
                            ->required()
                            ->default(1)
                            ->numeric(),

                            Forms\Components\TextInput::make('item_cost')
                            ->required()
                            ->label('Value')
                            ->numeric()
                            ->prefix('$'),

                            ])
                            ->columns(3),

                            ])
                            ->columnSpanFull(),



                            Forms\Components\Section::make('')
                            ->description('')
                            ->schema([

                                Forms\Components\Placeholder::make('totalitem')
                                ->content(function ($get,$set){
                                    $items = $get('items');

                                    return "Total item: ".count($items);
                                })
                                ->label(''),

                                Forms\Components\Placeholder::make('totqty')
                                ->content(function ($get,$set){
                                    $items = collect( $get('items'))
                                    ->pluck('quantity')
                                    ->sum();

                                    return "Total qty: ".$items;
                                })
                                ->label(''),


                                Forms\Components\Placeholder::make('item')
                                ->content(function ($get,$set){

                                    $items = collect( $get('items'))
                                    ->pluck('item_cost')
                                    ->sum();

                                    return "Subtotal: $".number_format($items,2);
                                })
                                ->label(''),

                                Forms\Components\Placeholder::make('item')
                                ->content(function ($get,$set){

                                    $items = collect( $get('items'))
                                    ->pluck('item_cost')
                                    ->sum();

                                    $total = $get('shipping_cost') + $items;

                                    return "Grand Total: $".number_format($total,2);
                                })
                                ->label(''),



                                ])
                                ->columns(4),


                                ])
                                ->columns(2),

                            ]);
                        }

                        public static function table(Table $table): Table
                        {
                            return $table
                            ->columns([
                                Tables\Columns\TextColumn::make('client.name')
                                ->searchable(),

                                Tables\Columns\TextColumn::make('shipping_cost')
                                ->numeric()
                                ->sortable()
                                ->badge()
                                ->color('info')
                                ->state(fn($record) => number_format($record->shipping_cost,2))
                                ->prefix('$'),

                                Tables\Columns\TextColumn::make('total')
                                ->numeric()
                                ->sortable()
                                ->badge()
                                ->state(fn($record) => number_format($record->total,2))
                                ->prefix('$'),

                                Tables\Columns\TextColumn::make('enteredby.name')
                                ->label('Recorded By')
                                ->searchable(),
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
                                        Tables\Actions\Action::make('print')
                                        ->color('warning')
                                        ->icon('heroicon-m-inbox-stack')
                                        ->url( fn ($record) => route('print-quotation', $record->id), shouldOpenInNewTab: true),
                                        Tables\Actions\Action::make("items")
                                        ->label('Quotation Items')
                                        ->color('info')
                                        ->icon('heroicon-m-truck')
                                        ->fillForm(function($record){
                                            return [
                                                'items' => $record->items
                                            ];
                                        })
                                        ->infolist([
                                            RepeatableEntry::make('items')
                                            ->label('')
                                            ->schema([
                                                ImageEntry::make('product.product_image')
                                                ->label('')
                                                ->columnSpan(2),

                                                TextEntry::make('product.name')
                                                ->state(fn($record) => $record->product?->name.' ('.$record->quantity.'x)')
                                                ->columnSpan(2),

                                                TextEntry::make('item_cost')
                                                ->label('Value'),
                                                ])
                                                ->columns(5)
                                                ])
                                                ->modalSubmitAction(false),




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
                                                    'index' => Pages\ListQuotations::route('/'),
                                                    'create' => Pages\CreateQuotation::route('/create'),
                                                    'edit' => Pages\EditQuotation::route('/{record}/edit'),
                                                ];
                                            }
                                        }

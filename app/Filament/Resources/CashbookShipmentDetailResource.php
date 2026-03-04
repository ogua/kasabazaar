<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashbookShipmentDetailResource\Pages;
use App\Models\CashbookShipmentDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashbookShipmentDetailResource extends Resource
{
    protected static ?string $model = CashbookShipmentDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Cashbook';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Shipment Details';
    protected static ?string $modelLabel = 'Shipment Expense';
    protected static ?string $pluralModelLabel = 'Shipment Details';
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shipment Info')
                    ->schema([
                        Forms\Components\DatePicker::make('date')->required(),
                        Forms\Components\TextInput::make('shipment_no')
                            ->label('Shipment No')
                            ->placeholder('e.g. 45th Shipment')
                            ->required(),
                        Forms\Components\TextInput::make('memo_no')
                            ->label('Memo No')
                            ->placeholder('e.g. Shipping Expenses 1')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Expense Breakdown (GH₵)')
                    ->schema([
                        Forms\Components\TextInput::make('import_duty')->label('Import Duty')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('feeding')->label('Feeding')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('internal_travels')->label('Internal Travels')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('loading_charges')->label('Loading Charges')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('accra_delivery')->label('Accra Delivery')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('port_to_warehouse')->label('Port → Warehouse')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('accra_to_kumasi')->label('Accra → Kumasi')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('kumasi_delivery')->label('Kumasi Delivery')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('stationaries')->label('Stationeries')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('port_entry')->label('Port Entry')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('communication')->label('Communication')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('replacement')->label('Replacement')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('police')->label('Police')->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('gas')->label('Gas / Fuel')->numeric()->prefix('₵')->default(0),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Total')
                    ->schema([
                        Forms\Components\Placeholder::make('total')
                            ->label('Computed Total')
                            ->content(fn ($record) => $record ? '₵' . number_format($record->total, 2) : 'Saved on create'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('shipment_no')->label('Shipment')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('memo_no')->label('Memo')->searchable(),
                Tables\Columns\TextColumn::make('import_duty')->label('Import Duty')->numeric(2)->prefix('₵')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('feeding')->numeric(2)->prefix('₵')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('loading_charges')->label('Loading')->numeric(2)->prefix('₵')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('accra_delivery')->label('Accra Del.')->numeric(2)->prefix('₵')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('accra_to_kumasi')->label('Accra→Kumasi')->numeric(2)->prefix('₵')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total')->label('Total')->numeric(2)->prefix('₵')->weight('bold')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shipment_no')
                    ->label('Shipment')
                    ->options(fn () => CashbookShipmentDetail::query()
                        ->distinct()
                        ->pluck('shipment_no', 'shipment_no')
                        ->toArray()),
            ])
            ->defaultSort('date', 'desc')
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
            'index'  => Pages\ListCashbookShipmentDetails::route('/'),
            'create' => Pages\CreateCashbookShipmentDetail::route('/create'),
            'edit'   => Pages\EditCashbookShipmentDetail::route('/{record}/edit'),
        ];
    }
}

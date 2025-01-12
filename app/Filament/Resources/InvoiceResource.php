<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Section::make('')
            ->description('')
            ->schema([
                Forms\Components\TextInput::make('shipment.client.name')
                ->label('Client')
                ->required()
                ->maxLength(36),
                Forms\Components\TextInput::make('total_amount')
                ->required()
                ->badge()
                ->numeric(),
                Forms\Components\TextInput::make('status')
                ->badge()
                ->required(),
                ])
                ->columns(2),

            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
            ->recordUrl('')
            ->columns([
                Tables\Columns\TextColumn::make('shipment.client.name')
                ->label('Client')
                ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                ->state(fn($record) => number_format($record->total_amount,2))
                ->numeric()
                ->badge()
                ->color('info')
                ->sortable(),
                Tables\Columns\TextColumn::make('status')
                ->badge(),
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
                        //Tables\Actions\EditAction::make(),
                        Tables\Actions\Action::make('Print Invoice')
                        ->icon('heroicon-m-receipt-percent')
                        ->color('success')
                        ->url( fn ($record) => route('shipping-invoice', $record->shipment_id), shouldOpenInNewTab: true),
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
                            'index' => Pages\ListInvoices::route('/'),
                            'create' => Pages\CreateInvoice::route('/create'),
                            'edit' => Pages\EditInvoice::route('/{record}/edit'),
                        ];
                    }
                }

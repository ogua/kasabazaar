<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Trip;
use Filament\Tables;
use Filament\Forms\Form;
use App\Enums\TripStatus;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\TripResource\Pages;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Fleet Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Trip Details')
                    ->schema([
                        Forms\Components\TextInput::make('trip_reference')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'TRP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT))
                            ->maxLength(50),
                        Forms\Components\Select::make('vehicle_id')
                            ->relationship('vehicle', 'registration_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('driver_id')
                            ->relationship('driver', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_date')
                            ->required(),
                        Forms\Components\DateTimePicker::make('actual_departure'),
                        Forms\Components\DateTimePicker::make('actual_arrival'),
                        Forms\Components\Select::make('status')
                            ->options(TripStatus::class)
                            ->default(TripStatus::Planned)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Route Information')
                    ->schema([
                        Forms\Components\TextInput::make('origin')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('destination')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('distance_km')
                            ->label('Distance (KM)')
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\TextInput::make('fuel_cost')
                            ->numeric()
                            ->prefix('GHS')
                            ->step(0.01),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Shipments')
                    ->schema([
                        Forms\Components\Select::make('shipments')
                            ->relationship('shipments', 'shipping_reference')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trip_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.registration_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('driver.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('origin')
                    ->limit(20),
                Tables\Columns\TextColumn::make('destination')
                    ->limit(20),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('shipments_count')
                    ->counts('shipments')
                    ->label('Shipments'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(TripStatus::class),
                Tables\Filters\SelectFilter::make('vehicle_id')
                    ->relationship('vehicle', 'registration_number'),
                Tables\Filters\SelectFilter::make('driver_id')
                    ->relationship('driver', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}

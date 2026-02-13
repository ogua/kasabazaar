<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Vehicle;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\VehicleStatus;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\VehicleResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\VehicleResource\RelationManagers;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Fleet Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vehicle Information')
                    ->schema([
                        Forms\Components\TextInput::make('registration_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        Forms\Components\TextInput::make('make')
                            ->label('Manufacturer')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('vehicle_type')
                            ->required(),
                        Forms\Components\Select::make('ownership_type')
                            ->label('Ownership')
                            ->required()
                            ->options([
                                'Owned' => 'Company Owned',
                                'Hired' => 'Hired Vehicle'
                            ])
                            ->searchable(),
                        Forms\Components\TextInput::make('model')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('year')
                            ->numeric()
                            ->minValue(1990)
                            ->maxValue(date('Y') + 1),
                        Forms\Components\ColorPicker::make('color')
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Capacity & Status')
                    ->schema([
                        Forms\Components\TextInput::make('max_weight_kg')
                            ->label('Capacity (KG)')
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\Select::make('status')
                            ->options(VehicleStatus::class)
                            ->default(VehicleStatus::Available)
                            ->required(),
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Maintenance & Insurance')
                    ->schema([
                        Forms\Components\DatePicker::make('last_service_date'),
                        Forms\Components\DatePicker::make('next_service_due'),
                        Forms\Components\TextInput::make('current_mileage')
                            ->label('Current Mileage')
                            ->numeric(),
                        Forms\Components\DatePicker::make('insurance_expiry'),
                        Forms\Components\DatePicker::make('roadworthy_expiry')
                            ->label('Roadworthy Expiry'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Additional Information')
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
                Tables\Columns\TextColumn::make('registration_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('make')
                    ->label('Manufacturer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable(),
                Tables\Columns\TextColumn::make('year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_service_due')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->next_service_due && $record->next_service_due < now() ? 'danger' : null),
                Tables\Columns\TextColumn::make('trips_count')
                    ->counts('trips')
                    ->label('Trips'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(VehicleStatus::class),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name'),
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}

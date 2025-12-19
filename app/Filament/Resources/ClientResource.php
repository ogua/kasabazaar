<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\City;
use Filament\Tables;
use App\Models\State;
use App\Models\Client;
use App\Models\Country;
use Nnjeim\World\World;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ClientResource\Pages;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ClientResource\RelationManagers;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Clients / Senders';

    protected static ?string $modelLabel = 'Client / Sender';

    protected static ?string $pluralModelLabel = 'Clients / Senders';

    // protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::schema());
    }


    public static function clientschema(): array
    {
        return [
            Forms\Components\Section::make('')
                ->description('')
                ->schema([

                    Forms\Components\TextInput::make('name')
                        ->label('Sender name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Sender email')
                        ->email(),

                    PhoneInput::make('phone')
                        ->label('Sender Phone number'),

                    Forms\Components\Textarea::make('address')
                        ->label('Sender address')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    public static function schema(): array
    {
        return [
            Forms\Components\Section::make('')
                ->description('')
                ->schema([

                    Forms\Components\TextInput::make('name')
                        ->label('Sender name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Sender email')
                        ->email(),

                    PhoneInput::make('phone')
                        ->label('Sender Phone number'),

                    Forms\Components\Select::make('country')
                        ->label('Sender country')
                        ->options(Country::pluck('name', 'id'))
                        ->preload()
                        ->searchable()
                        ->live(),

                    Forms\Components\Select::make('state_region')
                        ->label('Sender State/Region')
                        ->options(function ($get) {

                            if (blank($get('country'))) {
                                return [];
                            }

                            return State::where('country_id', $get('country'))->pluck('name', 'id');
                        })
                        ->searchable()
                        ->live(),

                    Forms\Components\Select::make('city')
                        ->label('Sender city')
                        ->options(function ($get) {

                            if (blank($get('state_region'))) {
                                return [];
                            }

                            return City::where('state_id', $get('state_region'))->pluck('name', 'id');
                        })
                        ->searchable(),

                    Forms\Components\Select::make('id_type')
                        ->label('Sender ID Type')
                        ->options([
                            'Drivers License' => 'Drivers License',
                            'Ghana Card' => 'Ghana Card',
                            'Voter ID Card' => 'Voter ID Card',
                            'Passport' => 'Passport',
                        ])
                        ->searchable(),

                    Forms\Components\TextInput::make('id_number')
                        ->label('Sender ID Number')
                        ->maxLength(255),


                    Forms\Components\Textarea::make('address')
                        ->label('Sender address')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl('')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Sender name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Sender email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Sender phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('countrystatecity')
                    ->label('Country')
                    ->state(fn($record) => "{$record->mcountry?->name}, {$record->mstate?->name}, {$record->mcity?->name}")
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}

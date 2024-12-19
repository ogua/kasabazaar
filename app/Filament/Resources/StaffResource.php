<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Filament\Resources\StaffResource\RelationManagers;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Human Resource';


    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Section::make('')
            ->description('')
            ->schema([

                Forms\Components\TextInput::make('branch_id')
                ->required()
                ->maxLength(36),
                Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
                Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                ->tel()
                ->required()
                ->maxLength(255),
                Forms\Components\TextInput::make('position')
                ->required()
                ->maxLength(255),

                ])
                ->columns(2),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->searchable(),
                Tables\Columns\TextColumn::make('branch_id')
                ->searchable(),
                Tables\Columns\TextColumn::make('name')
                ->searchable(),
                Tables\Columns\TextColumn::make('email')
                ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                ->searchable(),
                Tables\Columns\TextColumn::make('position')
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
                            'index' => Pages\ListStaff::route('/'),
                            'create' => Pages\CreateStaff::route('/create'),
                            'edit' => Pages\EditStaff::route('/{record}/edit'),
                        ];
                    }
                }

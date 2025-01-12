<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $title = 'Staff';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
        ->where('role',"branch_personnel")
        ->orderBy('created_at','desc');
    }

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Section::make('')
            ->description('')
            ->schema([
                Forms\Components\FileUpload::make('avatar')
                ->image()
                ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
                Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255),
                Forms\Components\TextInput::make('password')
                ->password()
                ->required()
                ->maxLength(255),
                Forms\Components\Select::make('roles')
                ->relationship('roles','name')
                ->multiple()
                ->searchable()
                ->preload()
                ->default(null)
                ->required(),
                Forms\Components\Select::make('branches')
                ->relationship()
                ->label('Branch')
                ->multiple()
                ->options(Branch::pluck('name','id'))
                ->preload()
                ->searchable()
                ->default(null),
                ])
                ->columns(2),

            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->searchable(),
                Tables\Columns\TextColumn::make('email')
                ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                ->badge(),
                Tables\Columns\TextColumn::make('branches.name')
                ->badge()
                ->color('info')
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
                            'index' => Pages\ListUsers::route('/'),
                            'create' => Pages\CreateUser::route('/create'),
                            'edit' => Pages\EditUser::route('/{record}/edit'),
                        ];
                    }
                }

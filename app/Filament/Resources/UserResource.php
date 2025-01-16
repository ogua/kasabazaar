<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Branch;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $title = 'Users';

    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
        //->where('role',"branch_personnel")
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
                            ->default(null)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('roles')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name'
                            )
                            ->multiple()
                            ->preload()
                            ->searchable(),

                            Forms\Components\Select::make('role')
                            ->options([
                                'customer' => 'Customer',
                                'admin' => 'Admin',
                                'branch_personnel' => 'Branch Personnel',
                            ])
                            ->searchable(),

                        Forms\Components\TextInput::make('email')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('email_verified_at'),

                        // Forms\Components\Toggle::make('is_active')
                        // ->required()
                        // ->columnSpanFull(),

                        Forms\Components\TextInput::make('password')
                        ->label('Change Password')
                        ->password()
                        ->revealable(filament()->arePasswordsRevealable())
                        ->required(fn (string $context): bool => $context === 'create')
                        ->rule(Password::default())
                        ->dehydrateStateUsing(fn ($state) => hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->same('passwordConfirmation')
                        ->live(onBlur: true)
                        ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute')),
                        
                        Forms\Components\TextInput::make('passwordConfirmation')
                        ->label(__('filament-panels::pages/auth/register.form.password_confirmation.label'))
                        ->password()
                        ->revealable(filament()->arePasswordsRevealable())
                        ->visible(fn ($get): bool => filled($get("password")))
                        ->required(fn ($get): bool => filled($get("password")))
                        ->dehydrated(false),
                        
                    ])
                    ->columns(2),
            ]);
    }


        public static function table(Table $table): Table
        {
            return $table
            ->recordUrl('')
            ->columns([
                Tables\Columns\ImageColumn::make('avatar'),
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

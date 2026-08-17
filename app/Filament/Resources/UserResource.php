<?php

namespace App\Filament\Resources;

use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Branch;
use App\Models\Investor;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $title = 'Users';

    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 8;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
        // ->where('role',"branch_personnel")
            ->orderBy('created_at', 'desc');
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
                            ->label('Account Type')
                            ->options([
                                'customer' => 'Customer (Client)',
                                'admin' => 'Admin',
                                'branch_personnel' => 'Branch Personnel (Staff)',
                                'investor' => 'Investor',
                                'vendor' => 'Vendor',
                            ])
                            ->searchable(),

                        Forms\Components\Select::make('status')
                            ->options(UserStatus::class)
                            ->default(UserStatus::Active)
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DateTimePicker::make('email_verified_at'),

                        // Forms\Components\Toggle::make('is_active')
                        // ->required()
                        // ->columnSpanFull(),

                        Forms\Components\Select::make('branches')
                            ->label('Branch')
                            ->relationship()
                            ->options(
                                Branch::all()->pluck('name', 'id')
                            )
                            ->preload()
                            ->multiple()
                            ->searchable(),

                        Forms\Components\Select::make('investor_id')
                            ->label('Linked Investor')
                            ->relationship('investor', 'name')
                            ->options(Investor::pluck('name', 'id'))
                            ->helperText('Link this login to an Investor record to grant access to the Investor portal and mobile app.')
                            ->preload()
                            ->searchable(),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required(fn (string $context): bool => $context === 'create')
                            ->rule(Password::default())
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->same('passwordConfirmation')
                            ->live(onBlur: true)
                            ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute')),

                        Forms\Components\TextInput::make('passwordConfirmation')
                            ->label(__('filament-panels::pages/auth/register.form.password_confirmation.label'))
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->visible(fn ($get): bool => filled($get('password')))
                            ->required(fn ($get): bool => filled($get('password')))
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
                Tables\Columns\TextColumn::make('role')
                    ->label('Account Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'customer' => 'Client',
                        'admin' => 'Admin',
                        'branch_personnel' => 'Staff',
                        'investor' => 'Investor',
                        'vendor' => 'Vendor',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'customer' => 'success',
                        'admin' => 'danger',
                        'branch_personnel' => 'primary',
                        'investor' => 'warning',
                        'vendor' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Permissions')
                    ->badge(),
                Tables\Columns\TextColumn::make('branches.name')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->badge()
                    ->color('success')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->badge()
                    ->color('primary')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('investor.name')
                    ->label('Investor')
                    ->badge()
                    ->color('warning')
                    ->toggleable()
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
                Tables\Filters\SelectFilter::make('role')
                    ->label('Account Type')
                    ->options([
                        'customer' => 'Client',
                        'admin' => 'Admin',
                        'branch_personnel' => 'Staff',
                        'investor' => 'Investor',
                        'vendor' => 'Vendor',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options(UserStatus::class),
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

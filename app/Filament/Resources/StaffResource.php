<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Staff;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\EmploymentStatus;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\StaffResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\StaffResource\RelationManagers;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Staff Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('employee_id')
                            ->label('Employee ID')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Auto-generated if left empty'),
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
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Employment Details')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('staff_role_id')
                            ->relationship('role', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $role = \App\Models\StaffRole::find($state);
                                    if ($role && $role->base_salary) {
                                        $set('salary', $role->base_salary);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('position')
                            ->required()
                            ->options([
                                'Shipping Manager' => 'Shipping Manager',
                                'Administrative Officer' => 'Administrative Officer',
                                'Cashier' => 'Cashier',
                                'Clerk' => 'Clerk',
                                'Security' => 'Security',
                                'Cleaner' => 'Cleaner',
                                'Warehouse Staff' => 'Warehouse Staff',
                                'Driver' => 'Driver',
                                'Other' => 'Other',
                            ])
                            ->default('Other')
                            ->helperText('Job title or position'),
                        Forms\Components\TextInput::make('salary')
                            ->numeric()
                            ->prefix('GHS')
                            ->step(0.01)
                            ->helperText('Monthly salary'),
                        Forms\Components\DatePicker::make('hire_date')
                            ->default(now()),
                        Forms\Components\Select::make('employment_status')
                            ->options(EmploymentStatus::class)
                            ->default(EmploymentStatus::Active)
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('User Account')
                    ->description('Create a user account for this staff member to access the system')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
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
                                    ->minLength(8),
                                Forms\Components\Select::make('role')
                                    ->options([
                                        'admin' => 'Admin',
                                        'branch_personnel' => 'Branch Personnel',
                                    ])
                                    ->default('branch_personnel')
                                    ->required(),
                            ])
                            ->helperText('Select existing user or create new'),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_id')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->icon('heroicon-m-phone'),
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('position')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('salary')
                    ->money('GHS')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('hire_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('employment_status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('user_id')
                    ->label('Has Account')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employment_status')
                    ->options(EmploymentStatus::class),
                Tables\Filters\SelectFilter::make('role')
                    ->relationship('role', 'name'),
                Tables\Filters\SelectFilter::make('branch')
                    ->relationship('branch', 'name'),
                Tables\Filters\TernaryFilter::make('user_id')
                    ->label('Has User Account')
                    ->nullable(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('createAccount')
                    ->label('Create Account')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn ($record) => !$record->user_id)
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->helperText('Password for the new user account'),
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'branch_personnel' => 'Branch Personnel',
                            ])
                            ->default('branch_personnel')
                            ->required(),
                    ])
                    ->action(function (Staff $record, array $data) {
                        $service = app(\App\Service\StaffAccountService::class);
                        $user = $service->createUserAccount($record, $data['password'], $data['role']);

                        if ($user) {
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('User account created')
                                ->body("User account created for {$record->name}")
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

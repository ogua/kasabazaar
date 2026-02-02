<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\StaffRole;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\StaffRoleResource\Pages;

class StaffRoleResource extends Resource
{
    protected static ?string $model = StaffRole::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Staff Management';
    protected static ?int $navigationSort = 1;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Role Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Unique code for this role (e.g., DRIVER, MANAGER)'),
                        Forms\Components\TextInput::make('base_salary')
                            ->label('Base Salary (GHS)')
                            ->numeric()
                            ->prefix('GH₵')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('base_salary')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('staff_count')
                    ->counts('staff')
                    ->label('Staff Members'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
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
            'index' => Pages\ListStaffRoles::route('/'),
            'create' => Pages\CreateStaffRole::route('/create'),
            'edit' => Pages\EditStaffRole::route('/{record}/edit'),
        ];
    }
}

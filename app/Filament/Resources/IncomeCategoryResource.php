<?php

namespace App\Filament\Resources;

use App\Enums\AccountType;
use App\Filament\Resources\IncomeCategoryResource\Pages;
use App\Models\IncomeCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IncomeCategoryResource extends Resource
{
    protected static ?string $model = IncomeCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 4;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Unique code for this category'),
                        Forms\Components\Select::make('chart_of_account_id')
                            ->label('Reports Under')
                            ->relationship(
                                'chartOfAccount',
                                'name',
                                fn ($query) => $query->where('type', AccountType::income->value)
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->code.' — '.$record->name)
                            ->searchable()
                            ->preload()
                            ->helperText('The statement line incomes in this category report under. Leave blank and they fall into the catch-all account.'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
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
                Tables\Columns\TextColumn::make('incomes_count')
                    ->counts('incomes')
                    ->label('Incomes'),
                Tables\Columns\TextColumn::make('chartOfAccount.name')
                    ->label('Reports Under')
                    ->placeholder('Unmapped — catch-all')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning'),

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
            'index' => Pages\ListIncomeCategories::route('/'),
            'create' => Pages\CreateIncomeCategory::route('/create'),
            'edit' => Pages\EditIncomeCategory::route('/{record}/edit'),
        ];
    }
}

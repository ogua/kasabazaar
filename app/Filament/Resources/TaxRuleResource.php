<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxRuleResource\Pages;
use App\Models\TaxRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaxRuleResource extends Resource
{
    protected static ?string $model = TaxRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 12;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tax Rule')
                    ->description('Only one active rule is applied at checkout — activating a new one should be paired with deactivating the old.')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('rate_percent')->numeric()->suffix('%')->required(),
                        Forms\Components\Toggle::make('is_active')->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('rate_percent')->suffix('%'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxRules::route('/'),
            'create' => Pages\CreateTaxRule::route('/create'),
            'edit' => Pages\EditTaxRule::route('/{record}/edit'),
        ];
    }
}

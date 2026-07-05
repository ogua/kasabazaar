<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestmentRateSettingResource\Pages;
use App\Models\InvestmentRateSetting;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvestmentRateSettingResource extends Resource
{
    protected static ?string $model = InvestmentRateSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Investors';

    protected static ?string $navigationLabel = 'Annual Rates';

    protected static ?int $navigationSort = 2;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company-Wide Default Rate')
                    ->description('Applies to every active investment held during this calendar year, unless a specific investment has its own rate override.')
                    ->schema([
                        Forms\Components\TextInput::make('year')
                            ->numeric()
                            ->required()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('annual_rate')
                            ->label('Annual Rate')
                            ->numeric()
                            ->suffix('%')
                            ->required(),

                        Forms\Components\Hidden::make('set_by')
                            ->default(fn () => Filament::auth()->id()),

                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->sortable(),

                Tables\Columns\TextColumn::make('annual_rate')
                    ->label('Annual Rate')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('setByUser.name')
                    ->label('Set By'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('year', 'desc')
            ->filters([])
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
            'index' => Pages\ListInvestmentRateSettings::route('/'),
            'create' => Pages\CreateInvestmentRateSetting::route('/create'),
            'edit' => Pages\EditInvestmentRateSetting::route('/{record}/edit'),
        ];
    }
}

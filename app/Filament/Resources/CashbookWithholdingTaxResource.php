<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashbookWithholdingTaxResource\Pages;
use App\Models\CashbookWithholdingTax;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashbookWithholdingTaxResource extends Resource
{
    protected static ?string $model = CashbookWithholdingTax::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Cashbook';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Withholding Tax';
    protected static ?string $modelLabel = 'WHT Entry';
    protected static ?string $pluralModelLabel = 'Withholding Tax';
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('WHT Entry')
                    ->schema([
                        Forms\Components\Select::make('month')
                            ->options([
                                1 => 'January', 2 => 'February', 3 => 'March',
                                4 => 'April',   5 => 'May',      6 => 'June',
                                7 => 'July',    8 => 'August',   9 => 'September',
                                10 => 'October', 11 => 'November', 12 => 'December',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('year')
                            ->numeric()
                            ->default((int) now()->format('Y'))
                            ->required(),
                        Forms\Components\TextInput::make('staff_name')
                            ->label('Staff Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('gross_amount')
                            ->label('Gross Amount')
                            ->numeric()
                            ->prefix('₵')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $gross = floatval($get('gross_amount'));
                                $rate  = floatval($get('rate') ?: 0.075);
                                $set('wht_amount', round($gross * $rate, 2));
                            }),
                        Forms\Components\TextInput::make('rate')
                            ->label('Rate (e.g. 0.075 = 7.5%)')
                            ->numeric()
                            ->default(0.075)
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $gross = floatval($get('gross_amount'));
                                $rate  = floatval($get('rate') ?: 0.075);
                                $set('wht_amount', round($gross * $rate, 2));
                            }),
                        Forms\Components\TextInput::make('wht_amount')
                            ->label('WHT Amount (auto)')
                            ->numeric()
                            ->prefix('₵')
                            ->readOnly(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('month')
                    ->formatStateUsing(fn ($state) => [
                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
                    ][$state] ?? $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')->sortable(),
                Tables\Columns\TextColumn::make('staff_name')->label('Staff')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('gross_amount')->label('Gross')->numeric(2)->prefix('₵'),
                Tables\Columns\TextColumn::make('rate')
                    ->formatStateUsing(fn ($state) => number_format((float) $state * 100, 1) . '%'),
                Tables\Columns\TextColumn::make('wht_amount')->label('WHT')->numeric(2)->prefix('₵')->weight('bold')->color('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->options(fn () => CashbookWithholdingTax::query()
                        ->distinct()
                        ->orderByDesc('year')
                        ->pluck('year', 'year')
                        ->toArray()),
                Tables\Filters\SelectFilter::make('month')
                    ->options([
                        1 => 'January', 2 => 'February', 3 => 'March',
                        4 => 'April',   5 => 'May',      6 => 'June',
                        7 => 'July',    8 => 'August',   9 => 'September',
                        10 => 'October', 11 => 'November', 12 => 'December',
                    ]),
            ])
            ->defaultSort('year', 'desc')
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
            'index'  => Pages\ListCashbookWithholdingTaxes::route('/'),
            'create' => Pages\CreateCashbookWithholdingTax::route('/create'),
            'edit'   => Pages\EditCashbookWithholdingTax::route('/{record}/edit'),
        ];
    }
}

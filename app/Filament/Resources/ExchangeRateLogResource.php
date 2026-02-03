<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ExchangeRateLog;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ExchangeRateLogResource\Pages;
use App\Filament\Resources\ExchangeRateLogResource\RelationManagers;

class ExchangeRateLogResource extends Resource
{
    protected static ?string $model = ExchangeRateLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Exchange Rates';

    protected static ?string $modelLabel = 'Exchange Rate';

    protected static ?string $navigationGroup = 'Finance';

    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Exchange Rate Information')
                    ->description('Set the exchange rate for currency conversion')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('from_currency')
                                    ->label('From Currency')
                                    ->options([
                                        'USD' => 'USD - US Dollar',
                                        'GHS' => 'GHS - Ghana Cedis',
                                        'EUR' => 'EUR - Euro',
                                        'GBP' => 'GBP - British Pound',
                                    ])
                                    ->default('USD')
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('to_currency')
                                    ->label('To Currency')
                                    ->options([
                                        'GHS' => 'GHS - Ghana Cedis',
                                        'USD' => 'USD - US Dollar',
                                        'EUR' => 'EUR - Euro',
                                        'GBP' => 'GBP - British Pound',
                                    ])
                                    ->default('GHS')
                                    ->required()
                                    ->native(false),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('rate')
                                    ->label('Exchange Rate')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.0001)
                                    ->step(0.0001)
                                    ->helperText('How much of the "To Currency" equals 1 unit of "From Currency"')
                                    ->placeholder('e.g., 12.5000')
                                    ->suffix('per 1 unit'),

                                Forms\Components\DatePicker::make('rate_date')
                                    ->label('Effective Date')
                                    ->required()
                                    ->default(today())
                                    ->helperText('Date when this rate becomes effective')
                                    ->native(false)
                                    ->displayFormat('M d, Y')
                                    ->maxDate(today()->addDays(7)),
                            ]),

                        Forms\Components\Select::make('source')
                            ->label('Source')
                            ->options([
                                'manual' => 'Manual Entry',
                                'bank' => 'Bank Rate',
                                'api' => 'API (Auto-fetched)',
                                'official' => 'Official Rate',
                            ])
                            ->default('manual')
                            ->required()
                            ->native(false)
                            ->helperText('Where this exchange rate comes from'),

                        Forms\Components\Hidden::make('recorded_by')
                            ->default(fn () => auth()->id()),
                    ]),

                Forms\Components\Section::make('Preview')
                    ->description('See how this rate converts amounts')
                    ->schema([
                        Forms\Components\Placeholder::make('conversion_preview')
                            ->label('')
                            ->content(function ($get) {
                                $rate = $get('rate');
                                $from = $get('from_currency') ?? 'USD';
                                $to = $get('to_currency') ?? 'GHS';

                                if (!$rate) {
                                    return new \Illuminate\Support\HtmlString('<p style="color: #6b7280;">Enter a rate above to see conversion preview</p>');
                                }

                                $examples = [
                                    1 => number_format(1 * $rate, 2),
                                    10 => number_format(10 * $rate, 2),
                                    100 => number_format(100 * $rate, 2),
                                    1000 => number_format(1000 * $rate, 2),
                                ];

                                $html = '<div style="font-family: monospace;">';
                                $html .= '<p style="font-weight: bold; margin-bottom: 8px;">Conversion Examples:</p>';
                                $html .= '<div style="display: grid; gap: 4px;">';

                                foreach ($examples as $amount => $converted) {
                                    $html .= sprintf(
                                        '<div style="padding: 4px 8px; background-color: #f3f4f6; border-radius: 4px;">%s %s = <span style="color: #059669; font-weight: 600;">%s %s</span></div>',
                                        $amount,
                                        $from,
                                        $converted,
                                        $to
                                    );
                                }

                                $html .= '</div></div>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->live(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('rate_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('rate_date')
                    ->label('Effective Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->rate_date->isToday() ? 'success' : ($record->rate_date->isFuture() ? 'warning' : 'gray')),

                Tables\Columns\TextColumn::make('from_currency')
                    ->label('From')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                Tables\Columns\IconColumn::make('conversion')
                    ->label('')
                    ->icon('heroicon-o-arrow-right')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('to_currency')
                    ->label('To')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Exchange Rate')
                    ->numeric(decimalPlaces: 4)
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('conversion_example')
                    ->label('Example')
                    ->formatStateUsing(function ($record) {
                        $amount = 100;
                        $converted = number_format($amount * $record->rate, 2);
                        return "{$amount} {$record->from_currency} = {$converted} {$record->to_currency}";
                    })
                    ->color('gray')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual' => 'warning',
                        'bank' => 'success',
                        'api' => 'info',
                        'official' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->placeholder('System')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('from_currency')
                    ->label('From Currency')
                    ->options([
                        'USD' => 'USD',
                        'GHS' => 'GHS',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP',
                    ]),

                Tables\Filters\SelectFilter::make('to_currency')
                    ->label('To Currency')
                    ->options([
                        'USD' => 'USD',
                        'GHS' => 'GHS',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP',
                    ]),

                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'manual' => 'Manual',
                        'bank' => 'Bank',
                        'api' => 'API',
                        'official' => 'Official',
                    ]),

                Tables\Filters\Filter::make('rate_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('rate_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('rate_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add New Rate')
                    ->icon('heroicon-o-plus'),
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
            'index' => Pages\ListExchangeRateLogs::route('/'),
            'create' => Pages\CreateExchangeRateLog::route('/create'),
            'edit' => Pages\EditExchangeRateLog::route('/{record}/edit'),
        ];
    }
}

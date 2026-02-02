<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Income;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Shipment;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\IncomeStatus;
use App\Enums\PaymentMethod;
use Filament\Resources\Resource;
use App\Service\ExchangeRateService;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\IncomeResource\Pages;

class IncomeResource extends Resource
{
    protected static ?string $model = Income::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 3;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shipment & Category')
                    ->schema([
                        Forms\Components\Select::make('shipment_id')
                            ->label('Shipment')
                            ->relationship('shipment', 'shipping_reference')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $shipment = Shipment::find($state);
                                    if ($shipment) {
                                        $set('branch_id', $shipment->branch_id);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('income_category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\TextInput::make('code')->required(),
                                Forms\Components\Textarea::make('description'),
                            ]),
                        Forms\Components\Hidden::make('branch_id'),
                        Forms\Components\Hidden::make('recorded_by')
                            ->default(fn () => auth()->id()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Income Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options(IncomeStatus::class)
                            ->required()
                            ->default(IncomeStatus::Pending),
                        Forms\Components\Select::make('payment_method')
                            ->options(PaymentMethod::class)
                            ->required(),
                        Forms\Components\TextInput::make('payer_name')
                            ->label('Payer Name'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Amount')
                    ->schema([
                        Forms\Components\TextInput::make('amount_usd')
                            ->label('Amount (USD)')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $amount = floatval($get('amount_usd'));
                                $rate = floatval($get('exchange_rate'));
                                if ($amount && $rate) {
                                    $set('amount_ghs', round($amount * $rate, 2));
                                }
                            }),
                        Forms\Components\TextInput::make('exchange_rate')
                            ->label('Exchange Rate')
                            ->numeric()
                            ->required()
                            ->default(fn () => app(ExchangeRateService::class)->getCurrentRate())
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $amount = floatval($get('amount_usd'));
                                $rate = floatval($get('exchange_rate'));
                                if ($amount && $rate) {
                                    $set('amount_ghs', round($amount * $rate, 2));
                                }
                            }),
                        Forms\Components\TextInput::make('amount_ghs')
                            ->label('Amount (GHS)')
                            ->numeric()
                            ->prefix('GH₵')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Additional Info')
                    ->schema([
                        Forms\Components\DatePicker::make('income_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\FileUpload::make('receipt_path')
                            ->label('Receipt')
                            ->directory('income-receipts')
                            ->columnSpanFull()
                            ->acceptedFileTypes(['application/pdf', 'image/*']),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipment.shipping_reference')
                    ->label('Shipment')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('amount_usd')
                    ->label('USD')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_ghs')
                    ->label('GHS')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge(),
                Tables\Columns\TextColumn::make('income_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('income_category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(IncomeStatus::class),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options(PaymentMethod::class),
                Tables\Filters\Filter::make('income_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('income_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('income_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('income_date', 'desc');
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
            'index' => Pages\ListIncomes::route('/'),
            'create' => Pages\CreateIncome::route('/create'),
            'edit' => Pages\EditIncome::route('/{record}/edit'),
        ];
    }
}

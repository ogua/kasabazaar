<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Expense;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Shipment;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\ExpenseStage;
use Filament\Resources\Resource;
use App\Service\ExchangeRateService;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ExpenseResource\Pages;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 1;

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
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $shipment = Shipment::find($state);
                                    if ($shipment) {
                                        $set('branch_id', $shipment->branch_id);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('expense_category_id')
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

                Forms\Components\Section::make('Expense Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('expense_stage')
                            ->options(ExpenseStage::class)
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('vendor_name')
                            ->label('Vendor/Supplier'),
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
                        Forms\Components\DatePicker::make('expense_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\FileUpload::make('receipt_path')
                            ->label('Receipt')
                            ->directory('receipts')
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
                Tables\Columns\TextColumn::make('expense_stage')
                    ->badge(),
                Tables\Columns\TextColumn::make('expense_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                Tables\Filters\SelectFilter::make('expense_stage')
                    ->options(ExpenseStage::class),
                Tables\Filters\Filter::make('expense_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('expense_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('expense_date', '<=', $date));
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
            ->defaultSort('expense_date', 'desc');
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}

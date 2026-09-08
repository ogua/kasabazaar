<?php

namespace App\Filament\Resources;

use App\Enums\ExpenseScope;
use App\Enums\ExpenseStage;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Service\ExchangeRateService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                Forms\Components\Section::make('What is this expense for?')
                    ->schema([
                        Forms\Components\Radio::make('expense_for')
                            ->label('Record this expense against')
                            ->options(ExpenseScope::class)
                            ->default(ExpenseScope::Shipment->value)
                            ->required()
                            ->live()
                            ->inline()
                            ->columnSpanFull()
                            ->afterStateUpdated(function (Set $set, $state): void {
                                if ($state === ExpenseScope::Container->value) {
                                    $set('shipment_id', null);
                                } else {
                                    $set('container_number', null);
                                }
                            }),
                        Forms\Components\Select::make('shipment_id')
                            ->label('Shipment')
                            ->relationship('shipment', 'shipping_reference')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (Get $get): bool => $get('expense_for') !== ExpenseScope::Container->value)
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $shipment = Shipment::find($state);
                                    if ($shipment) {
                                        $set('branch_id', $shipment->branch_id);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('container_number')
                            ->label('Container')
                            ->options(fn (): array => static::containerOptions())
                            ->searchable()
                            ->required()
                            ->visible(fn (Get $get): bool => $get('expense_for') === ExpenseScope::Container->value)
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (! $state) {
                                    return;
                                }
                                $branchId = Shipment::where('container_number', $state)->value('branch_id');
                                $set('branch_id', $branchId ?? Branch::query()->value('id'));
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
                    ->description('Enter either the USD or the GHS amount — the other is calculated from the exchange rate.')
                    ->schema([
                        Forms\Components\TextInput::make('amount_usd')
                            ->label('Amount (USD)')
                            ->numeric()
                            ->requiredWithout('amount_ghs')
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, $state): void {
                                $usd = floatval($state);
                                $rate = floatval($get('exchange_rate'));
                                if ($usd > 0 && $rate > 0) {
                                    $set('amount_ghs', round($usd * $rate, 2));
                                }
                            }),
                        Forms\Components\TextInput::make('exchange_rate')
                            ->label('Exchange Rate')
                           // ->numeric()
                            ->required()
                            ->default(fn () => app(ExchangeRateService::class)->getCurrentRate())
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, $state): void {
                                $rate = floatval($state);
                                if ($rate <= 0) {
                                    return;
                                }
                                $usd = floatval($get('amount_usd'));
                                if ($usd > 0) {
                                    $set('amount_ghs', round($usd * $rate, 2));

                                    return;
                                }
                                $ghs = floatval($get('amount_ghs'));
                                if ($ghs > 0) {
                                    $set('amount_usd', round($ghs / $rate, 2));
                                }
                            }),
                        Forms\Components\TextInput::make('amount_ghs')
                            ->label('Amount (GHS)')
                            ->numeric()
                            ->requiredWithout('amount_usd')
                            ->prefix('GH₵')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, $state): void {
                                $ghs = floatval($state);
                                $rate = floatval($get('exchange_rate'));
                                if ($ghs > 0 && $rate > 0) {
                                    $set('amount_usd', round($ghs / $rate, 2));
                                }
                            }),
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

    /**
     * Container numbers that shipments have been assigned to, plus any tracked
     * container-clearance records, keyed for a Select ("51" => "CON51").
     *
     * @return array<string, string>
     */
    protected static function containerOptions(): array
    {
        $fromShipments = Shipment::query()
            ->whereNotNull('container_number')
            ->distinct()
            ->pluck('container_number');

        $fromContainers = ShipmentContainer::query()->pluck('container_number');

        return $fromShipments
            ->merge($fromContainers)
            ->map(fn ($number) => (string) $number)
            ->unique()
            ->sortByDesc(fn (string $number) => (int) $number)
            ->mapWithKeys(fn (string $number) => [$number => 'CON'.$number])
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expense_for')
                    ->label('For')
                    ->badge(),
                Tables\Columns\TextColumn::make('subject_reference')
                    ->label('Shipment / Container')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('container_number', 'like', "%{$search}%")
                            ->orWhereHas('shipment', fn (Builder $q) => $q->where('shipping_reference', 'like', "%{$search}%"));
                    })
                    ->placeholder('—'),
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
                Tables\Filters\SelectFilter::make('expense_for')
                    ->label('For')
                    ->options(ExpenseScope::class),
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

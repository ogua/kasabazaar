<?php

namespace App\Filament\Resources;

use App\Enums\ShipmentRequestStatus;
use App\Filament\Resources\ShipmentRequestResource\Pages;
use App\Models\Product;
use App\Models\ShipmentRequest;
use App\Services\ShipmentRequestApprovalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class ShipmentRequestResource extends Resource
{
    protected static ?string $model = ShipmentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'Shipment Requests';

    // protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected static bool $isScopedToTenant = false;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'submitted')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\Placeholder::make('client_summary')
                            ->label('Client')
                            ->content(fn (?ShipmentRequest $record): string => trim(($record?->client?->name ?? '—')
                                .($record?->client?->phone ? " ({$record->client->phone})" : ''))),

                        Forms\Components\Placeholder::make('status_summary')
                            ->label('Status')
                            ->content(fn (?ShipmentRequest $record): string => $record?->status?->getLabel() ?? '—'),

                        Forms\Components\TextInput::make('pickup_location')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DateTimePicker::make('preferred_pickup_at')
                            ->label('Preferred pickup')
                            ->seconds(false)
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Receivers & Items')
                    ->schema([
                        Forms\Components\Repeater::make('receivers')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                                Forms\Components\TextInput::make('phone')->tel()->required()->maxLength(30),
                                Forms\Components\TextInput::make('country')->maxLength(100),
                                Forms\Components\TextInput::make('state_region')->label('State / Region')->maxLength(100),
                                Forms\Components\TextInput::make('city')->maxLength(100),
                                Forms\Components\TextInput::make('address')->maxLength(500),

                                Forms\Components\Repeater::make('items')
                                    ->schema(static::itemSchema())
                                    ->columns(12)
                                    ->minItems(1)
                                    ->defaultItems(1)
                                    ->addActionLabel('Add Item')
                                    ->reorderable(false)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->minItems(1)
                            ->addActionLabel('Add Receiver')
                            ->reorderable(false)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->collapsible(),
                    ]),
            ]);
    }

    /**
     * One requested item. The value always comes from the selected product and is
     * re-applied on save by ShipmentRequest::normaliseReceivers().
     *
     * @return array<int, Forms\Components\Component>
     */
    private static function itemSchema(): array
    {
        return [
            Forms\Components\Select::make('product_id')
                ->label('Product')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => Product::query()
                    ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                    ->orderBy('name')
                    ->limit(50)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name)
                ->live()
                ->required(fn (Forms\Get $get): bool => blank($get('description')))
                ->afterStateUpdated(function (?string $state, Forms\Set $set): void {
                    $product = $state ? Product::find($state) : null;

                    if ($product) {
                        $set('description', $product->name);
                        $set('estimated_value', number_format((float) $product->value, 2, '.', ''));
                    }
                })
                ->createOptionForm([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('category')->default('general')->required()->maxLength(255),
                    Forms\Components\TextInput::make('value')->label('Value (USD)')->numeric()->minValue(0)->required(),
                    Forms\Components\TextInput::make('weight')->numeric()->minValue(0),
                ])
                ->createOptionModalHeading('Add a new product')
                ->createOptionUsing(fn (array $data): string => Product::create([
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'value' => $data['value'],
                    'weight' => $data['weight'] ?? null,
                    'branch_id' => null,
                ])->getKey())
                ->helperText(fn (Forms\Get $get): ?string => blank($get('product_id')) && filled($get('description'))
                    ? "Client asked for a new product: \"{$get('description')}\". Pick it from the list or add it with +."
                    : null)
                ->columnSpan(5),

            Forms\Components\Hidden::make('description'),

            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->default(1)
                ->required()
                ->columnSpan(2),

            Forms\Components\TextInput::make('estimated_value')
                ->label('Value (USD)')
                ->prefix('$')
                ->disabled()
                ->dehydrated()
                ->placeholder('From product')
                ->columnSpan(3),

            Forms\Components\TextInput::make('details')
                ->label('Description')
                ->maxLength(500)
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.phone')
                    ->label('Phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'public_link' ? 'Pickup link' : 'App')
                    ->color(fn (?string $state): string => $state === 'public_link' ? 'warning' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pickup_location')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->pickup_location),

                Tables\Columns\TextColumn::make('preferred_pickup_at')
                    ->label('Preferred Pickup')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ShipmentRequestStatus::class),

                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'app' => 'App',
                        'public_link' => 'Pickup link',
                    ]),
            ])
            ->actions([
                Action::make('markUnderReview')
                    ->label('Mark Under Review')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record) => $record->status->value === 'submitted')
                    ->action(function ($record) {
                        $record->update(['status' => 'under_review']);
                        Notification::make()->title('Marked as Under Review')->success()->send();
                    }),

                static::convertAction(),

                Action::make('approve')
                    ->label('Quick Approve')
                    ->icon('heroicon-o-bolt')
                    ->color('gray')
                    ->modalDescription('Creates the shipment straight from the request with these few details. Use "Convert to Shipment" to review everything in the full shipment form instead.')
                    ->visible(fn ($record) => in_array($record->status->value, ['submitted', 'under_review']))
                    ->fillForm(fn (ShipmentRequest $record): array => [
                        'shipping_cost' => $record->itemsTotal(),
                        'vat_percentage' => 0,
                        'insurance_accepted' => false,
                        'insurance' => 0,
                        'notes' => $record->notes,
                    ])
                    ->form([
                        Forms\Components\TextInput::make('shipping_cost')
                            ->label('Shipping cost')
                            ->helperText('Pre-filled with the total value of the requested items.')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('USD')
                            ->nullable(),
                        Forms\Components\TextInput::make('vat_percentage')
                            ->label('VAT / Tax')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0)
                            ->nullable(),
                        Forms\Components\Toggle::make('insurance_accepted')
                            ->label('Client accepts insurance')
                            ->live()
                            ->afterStateUpdated(fn (bool $state, Forms\Set $set) => $state ?: $set('insurance', 0)),
                        Forms\Components\TextInput::make('insurance')
                            ->label('Insurance amount')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('USD')
                            ->default(0)
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('insurance_accepted'))
                            ->required(fn (Forms\Get $get): bool => (bool) $get('insurance_accepted')),
                        Forms\Components\Textarea::make('notes')
                            ->label('Staff Notes')
                            ->nullable(),
                        Forms\Components\Select::make('shipment_type')
                            ->label('Shipment Type')
                            ->options([
                                'new' => 'New Shipment',
                                'existing' => 'Add to Existing Shipment',
                            ])
                            ->default('new')
                            ->live(),
                        Forms\Components\Select::make('existing_shipment_id')
                            ->label('Existing Shipment')
                            ->options(fn () => \App\Models\Shipment::query()
                                ->whereIn('status', ['pending', 'pickup'])
                                ->orderByDesc('created_at')
                                ->limit(100)
                                ->pluck('shipping_reference', 'id'))
                            ->searchable()
                            ->visible(fn (Forms\Get $get) => $get('shipment_type') === 'existing')
                            ->requiredIf('shipment_type', 'existing'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $service = new ShipmentRequestApprovalService;
                            $shipment = $service->approve($record->load('client'), auth()->user(), $data);
                            Notification::make()
                                ->title('Request Approved')
                                ->body("Shipment {$shipment->shipping_reference} created.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Approval Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status->value !== 'approved' && $record->status->value !== 'rejected')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->required()
                            ->label('Reason for Rejection'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->title('Request Rejected')->warning()->send();
                    }),

                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (ShipmentRequest $record): bool => $record->isEditable() && static::canEdit($record)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Opens the full create-shipment wizard pre-filled from the request.
     */
    public static function convertAction(): Action
    {
        return Action::make('convert')
            ->label('Convert to Shipment')
            ->icon('heroicon-o-arrow-right-circle')
            ->color('success')
            ->visible(fn (ShipmentRequest $record): bool => $record->isEditable() && ! $record->shipment_id && ShipmentResource::canCreate())
            ->url(fn (ShipmentRequest $record): string => ShipmentResource::getUrl('from-request', ['shipmentRequest' => $record->id]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipmentRequests::route('/'),
            'view' => Pages\ViewShipmentRequest::route('/{record}'),
            'edit' => Pages\EditShipmentRequest::route('/{record}/edit'),
        ];
    }
}

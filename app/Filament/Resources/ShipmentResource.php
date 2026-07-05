<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\City;
use Filament\Tables;
use App\Models\State;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Country;
use App\Models\Product;
use Nnjeim\World\World;
use App\Models\Shipment;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\ShippingStatus;
use App\Models\ShipmentMedia;
use App\Models\ShipmentUpdate;
use Filament\Facades\Filament;
use Livewire\Attributes\Layout;
use App\Models\CustomerFeedback;
use Filament\Resources\Resource;
use libphonenumber\NumberFormat;
use App\Models\ShipmentContainer;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Wizard;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use PragmaRX\Countries\Package\Countries;
use App\Filament\Resources\ClientResource;
use Filament\Tables\Enums\ActionsPosition;
use App\Filament\Resources\ProductResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use App\Filament\Resources\ShipmentResource\Pages;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ShipmentResource\RelationManagers;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('containerStatus')
            ->orderBy('container_number', 'desc')
            ->orderBy('created_at', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                // Step 1: Client/Sender Information
                Wizard\Step::make('Sender Info')
                    ->icon('heroicon-o-user')
                    ->description('Client & route details')
                    ->schema([
                        Forms\Components\Section::make('Client Details')
                            ->schema([
                                Forms\Components\TextInput::make('shipping_reference')
                                    ->label('Shipping Reference')
                                    ->required()
                                    ->placeholder('e.g., SHP-001')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('client_id')
                                    ->label('Select Client (Sender)')
                                    ->required()
                                    ->relationship(name: 'client', titleAttribute: 'fullname_branch')
                                    ->getOptionLabelFromRecordUsing(fn($record) => $record->fullname_branch)
                                    //->createOptionForm(ClientResource::clientschema())
                                    //->editOptionForm(ClientResource::clientschema())
                                    ->preload()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $hasShipments = Shipment::where('client_id', $state)->exists();
                                            $set('client_existence', $hasShipments ? 'returning-client' : 'new-client');
                                        }
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Select::make('client_existence')
                                    ->label('Client Type')
                                    ->options([
                                        'new-client' => 'New Client',
                                        'returning-client' => 'Returning Client',
                                    ])
                                    ->default('new-client')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),

                                Forms\Components\Hidden::make('tracking_number'),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Shipping Route')
                            ->schema([
                                Forms\Components\Select::make('origin_branch_id')
                                    ->label('Shipping From (State)')
                                    ->required()
                                    ->options([
                                        'Michigan' => 'Michigan',
                                        'Illinois' => 'Illinois',
                                        'Indiana' => 'Indiana',
                                        'New York' => 'New York',
                                        'New Jersey' => 'New Jersey',
                                        'Kentucky' => 'Kentucky',
                                        'Ohio' => 'Ohio',
                                        'Ghana' => 'Ghana',
                                        'Others' => 'Others',
                                    ])
                                    ->searchable()
                                    ->native(false),

                                Forms\Components\Select::make('destination_branch_id')
                                    ->label('To')
                                    ->required()
                                    ->options([
                                        'Michigan' => 'Michigan',
                                        'Illinois' => 'Illinois',
                                        'Indiana' => 'Indiana',
                                        'New York' => 'New York',
                                        'New Jersey' => 'New Jersey',
                                        'Kentucky' => 'Kentucky',
                                        'Ohio' => 'Ohio',
                                        'Ghana' => 'Ghana',
                                        'Others' => 'Others',
                                    ])
                                    ->searchable()
                                    ->default('Ghana')
                                    ->native(false),
                            ])
                            ->columns(2),

                        Forms\Components\Hidden::make('status')->default('pickup'),
                        Forms\Components\Hidden::make('shipping_cost')->default(0),
                    ]),

                // Step 2: Items & Receiver Mode Selection
                Wizard\Step::make('Items & Receivers')
                    ->icon('heroicon-o-cube')
                    ->description('Add items and receivers')
                    ->schema([
                        // Receiver Mode Selection - Quick toggle at the top
                        // Forms\Components\Section::make('How many receivers?')
                        //     ->description('Select based on your shipment needs')
                        //     ->schema([
                        //         Forms\Components\Radio::make('receiver_mode')
                        //             ->label('')
                        //             ->options([
                        //                 'single' => 'Single Receiver - All items go to one person (fastest)',
                        //                 'multiple' => 'Multiple Receivers - Items go to different people',
                        //             ])
                        //             ->default('multiple')
                        //             ->live()
                        //             ->inline()
                        //             ->columnSpanFull(),

                        //         Hidden::make('receiver_mode')->default('multiple'),
                        //     ])
                        //     ->compact(),

                         Hidden::make('receiver_mode')->default('multiple'),

                        // SINGLE RECEIVER MODE - Streamlined fast entry
                        Forms\Components\Section::make('Receiver Details')
                            ->description('Enter receiver information')
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('single_receiver_name')
                                            ->label('Full Name')
                                            ->required()
                                            ->placeholder('Receiver\'s name'),

                                        PhoneInput::make('single_receiver_phone')
                                            ->label('Phone'),

                                        Forms\Components\TextInput::make('single_receiver_email')
                                            ->email()
                                            ->label('Email')
                                            ->placeholder('email@example.com'),

                                        Forms\Components\Select::make('single_receiver_id_type')
                                            ->label('ID Type')
                                            ->hidden()
                                            ->options([
                                                'Drivers License' => 'Drivers License',
                                                'Ghana Card' => 'Ghana Card',
                                                'Voter ID Card' => 'Voter ID Card',
                                                'Passport' => 'Passport',
                                            ])
                                            ->native(false),
                                    ]),

                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('single_receiver_id_number')
                                            ->label('ID Number'),

                                        Forms\Components\Select::make('single_receiver_country')
                                            ->label('Country')
                                            ->options(Country::pluck('name', 'id'))
                                            ->searchable()
                                            ->live()
                                            ->native(false),

                                        Forms\Components\Select::make('single_receiver_state')
                                            ->label('State/Region')
                                            ->options(function ($get) {
                                                if (blank($get('single_receiver_country'))) {
                                                    return [];
                                                }
                                                return State::where('country_id', $get('single_receiver_country'))->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->live()
                                            ->native(false),

                                        Forms\Components\Select::make('single_receiver_city')
                                            ->label('City')
                                            ->options(function ($get) {
                                                if (blank($get('single_receiver_state'))) {
                                                    return [];
                                                }
                                                return City::where('state_id', $get('single_receiver_state'))->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->native(false),
                                    ]),

                                Forms\Components\Textarea::make('single_receiver_address')
                                    ->label('Delivery Address')
                                    ->placeholder('Full delivery address')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn($get) => $get('receiver_mode') === 'single')
                            ->collapsible(),

                        // Items Section - For Single Receiver (items entered once, auto-copied)
                        Forms\Components\Section::make('Pickup Items')
                            ->description('Add all items - they will be assigned to the receiver above')
                            ->schema([
                                TableRepeater::make('pickupitems')
                                    ->label('')
                                    ->live()
                                    ->relationship()
                                    ->addActionLabel('+ Add Item')
                                    ->defaultItems(1)
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Product')
                                            ->required()
                                            ->relationship('product', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->createOptionForm(ProductResource::formself())
                                            ->editOptionForm(ProductResource::formself()),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Qty')
                                            ->required()
                                            ->default(1)
                                            ->numeric()
                                            ->minValue(1),
                                        Forms\Components\TextInput::make('item_cost')
                                            ->required()
                                            ->label('Value ($)')
                                            ->default(0)
                                            ->numeric()
                                            ->prefix('$'),
                                    ]),

                                // Summary
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('item_count')
                                            ->content(fn($get) => 'Items: ' . count($get('pickupitems') ?? []))
                                            ->label(''),

                                        Forms\Components\Placeholder::make('qty_total')
                                            ->content(function ($get) {
                                                $items = $get('pickupitems') ?? [];
                                                return 'Qty: ' . collect($items)->pluck('quantity')->sum();
                                            })
                                            ->label(''),

                                        Forms\Components\Placeholder::make('cost_total')
                                            ->content(function ($get, $set) {
                                                $items = $get('pickupitems') ?? [];
                                                $total = collect($items)->pluck('item_cost')->sum();
                                                $set('shipping_cost', $total);
                                                $set('total', $total);
                                                return 'Total: $' . number_format($total, 2);
                                            })
                                            ->label(''),
                                    ]),
                            ])
                            ->visible(fn($get) => $get('receiver_mode') === 'single'),

                        // MULTIPLE RECEIVERS MODE - Full form with nested items
                        Forms\Components\Section::make('Receivers & Their Items')
                            ->description('Add receivers and assign items to each')
                            ->schema([
                                Forms\Components\Repeater::make('receivers')
                                    ->relationship('receivers')
                                    ->label('')
                                    ->addActionLabel('+ Add Receiver')
                                    ->defaultItems(1)
                                    ->collapsible()
                                    ->cloneable()
                                    ->itemLabel(fn(array $state): ?string => $state['receiver_name'] ?? 'New Receiver')
                                    ->schema([
                                        Forms\Components\Toggle::make('sender_is_receiver')
                                            ->label('Sender is Receiver')
                                            ->dehydrated(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($state) {
                                                    $clientId = $get('../../client_id');
                                                    if ($clientId) {
                                                        $client = Client::find($clientId);
                                                        if ($client) {
                                                            $set('receiver_name', $client->name);
                                                            $set('receiver_phone', $client->phone);
                                                            $set('receiver_email', $client->email);
                                                            $set('country', $client->country);
                                                            $set('state_region', $client->state_region);
                                                            $set('city', $client->city);
                                                            $set('address', $client->address);
                                                        }
                                                    }
                                                }
                                            })
                                            ->columnSpanFull(),

                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('receiver_name')
                                                    ->label('Full Name')
                                                    ->required()
                                                    ->placeholder('Receiver\'s name'),

                                                PhoneInput::make('receiver_phone')
                                                    ->label('Phone'),

                                                Forms\Components\TextInput::make('receiver_email')
                                                    ->email()
                                                    ->label('Email'),

                                                Forms\Components\Select::make('receiver_id_type')
                                                    ->label('ID Type')
                                                    ->options([
                                                        'Drivers License' => 'Drivers License',
                                                        'Ghana Card' => 'Ghana Card',
                                                        'Voter ID Card' => 'Voter ID Card',
                                                        'Passport' => 'Passport',
                                                    ])
                                                    ->native(false),
                                            ]),

                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('receiver_id_number')
                                                    ->label('ID Number'),

                                                Forms\Components\Select::make('country')
                                                    ->label('Country')
                                                    ->options(Country::pluck('name', 'id'))
                                                    ->searchable()
                                                    ->live()
                                                    ->native(false),

                                                Forms\Components\Select::make('state_region')
                                                    ->label('State/Region')
                                                    ->options(function ($get) {
                                                        if (blank($get('country'))) {
                                                            return [];
                                                        }
                                                        return State::where('country_id', $get('country'))->pluck('name', 'id');
                                                    })
                                                    ->searchable()
                                                    ->live()
                                                    ->native(false),

                                                Forms\Components\Select::make('city')
                                                    ->label('City')
                                                    ->options(function ($get) {
                                                        if (blank($get('state_region'))) {
                                                            return [];
                                                        }
                                                        return City::where('state_id', $get('state_region'))->pluck('name', 'id');
                                                    })
                                                    ->searchable()
                                                    ->native(false),
                                            ]),

                                        Forms\Components\Textarea::make('address')
                                            ->label('Address')
                                            ->rows(1)
                                            ->columnSpanFull(),

                                        Forms\Components\Section::make('Items for this Receiver')
                                            ->schema([
                                                TableRepeater::make('items')
                                                    ->label('')
                                                    ->live()
                                                    ->relationship()
                                                    ->addActionLabel('+ Add Item')
                                                    ->defaultItems(1)
                                                    ->schema([
                                                        Forms\Components\Hidden::make('box_no')->default(null),
                                                        Forms\Components\Select::make('product_id')
                                                            ->label('Product')
                                                            ->required()
                                                            ->relationship('product', 'name')
                                                            ->preload()
                                                            ->searchable()
                                                            ->createOptionForm(ProductResource::formself())
                                                            ->editOptionForm(ProductResource::formself()),
                                                        Forms\Components\TextInput::make('quantity')
                                                            ->label('Qty')
                                                            ->required()
                                                            ->default(1)
                                                            ->numeric()
                                                            ->minValue(1),
                                                        Forms\Components\TextInput::make('item_cost')
                                                            ->required()
                                                            ->label('Value ($)')
                                                            ->numeric()
                                                            ->prefix('$'),
                                                    ]),
                                            ])
                                            ->compact()
                                            ->columnSpanFull(),
                                    ]),

                                // Multi-receiver Summary
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('multi_item_count')
                                            ->content(function ($get) {
                                                $items = array_column($get('receivers') ?? [], 'items');
                                                return 'Items: ' . array_sum(array_map('count', $items));
                                            })
                                            ->label(''),

                                        Forms\Components\Placeholder::make('multi_qty_total')
                                            ->content(function ($get) {
                                                $receivers = $get('receivers') ?? [];
                                                $totqty = 0;
                                                foreach ($receivers as $receiver) {
                                                    if (isset($receiver['items']) && is_array($receiver['items'])) {
                                                        $totqty += array_sum(array_column($receiver['items'], 'quantity'));
                                                    }
                                                }
                                                return 'Qty: ' . $totqty;
                                            })
                                            ->label(''),

                                        Forms\Components\Placeholder::make('multi_cost_total')
                                            ->content(function ($get, $set) {
                                                $receivers = $get('receivers') ?? [];
                                                $subtotal = 0;
                                                foreach ($receivers as $receiver) {
                                                    if (isset($receiver['items']) && is_array($receiver['items'])) {
                                                        $subtotal += array_sum(array_column($receiver['items'], 'item_cost'));
                                                    }
                                                }
                                                $set('shipping_cost', $subtotal);
                                                $set('total', $subtotal);
                                                return 'Total: $' . number_format($subtotal, 2);
                                            })
                                            ->label(''),
                                    ]),
                            ])
                            ->visible(fn($get) => $get('receiver_mode') === 'multiple'),
                    ]),

                // Step 3: Payment (Only on Edit)
                Wizard\Step::make('Payment')
                    ->icon('heroicon-o-banknotes')
                    ->description('Record payments')
                    ->visible(fn($operation) => $operation === 'edit')
                    ->schema([
                        Forms\Components\Section::make('Payment Records')
                            ->schema([
                                Forms\Components\Repeater::make('Payments')
                                    ->label('')
                                    ->relationship('payments')
                                    ->addActionLabel('+ Add Payment')
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Hidden::make('branch_id')->default(Filament::getTenant()->id),

                                                Forms\Components\DateTimePicker::make('paid_on')
                                                    ->label('Date')
                                                    ->default(now())
                                                    ->required(),

                                                Forms\Components\Select::make('paying_method')
                                                    ->label('Method')
                                                    ->options([
                                                        'CASH' => 'Cash',
                                                        'Zelle' => 'Zelle',
                                                        'Cash App' => 'Cash App',
                                                        'BANK TRANSFER' => 'Bank Transfer',
                                                        'CREDIT/DEBIT CARD' => 'Card',
                                                        'CHEQUE' => 'Cheque',
                                                        'PAYPAL' => 'PayPal',
                                                        'WAIVED' => 'Waived',
                                                    ])
                                                    ->live()
                                                    ->required()
                                                    ->native(false),

                                                Forms\Components\TextInput::make('amount')
                                                    ->label('Amount')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->required(),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('bankname')
                                                    ->label('Bank')
                                                    ->visible(fn($get): bool => $get('paying_method') === 'BANK TRANSFER'),

                                                Forms\Components\TextInput::make('accountnumber')
                                                    ->label('Account #')
                                                    ->visible(fn($get): bool => $get('paying_method') === 'BANK TRANSFER'),

                                                Forms\Components\TextInput::make('cheque_no')
                                                    ->label('Cheque #')
                                                    ->visible(fn($get): bool => $get('paying_method') === 'CHEQUE')
                                                    ->columnSpanFull(),
                                            ]),

                                        Forms\Components\Hidden::make('user_id')->default(fn() => auth()->id()),
                                        Forms\Components\Hidden::make('change')->default(0),
                                        Forms\Components\Hidden::make('payment_ref')->default(fn() => 'REF:' . date('YmdHis')),

                                        Forms\Components\Textarea::make('payment_note')
                                            ->label('Notes')
                                            ->rows(1)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),

                // Step 4: Review & Complete
                Wizard\Step::make('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->description('Review & save')
                    ->schema([
                        Forms\Components\Section::make('Cost Summary')
                            ->schema([
                                Forms\Components\TextInput::make('shipping_cost')
                                    ->label('Shipping Cost')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0),

                                Forms\Components\TextInput::make('total')
                                    ->label('Grand Total')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0)
                                    ->extraAttributes(['class' => 'font-bold text-xl']),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Status')
                            ->schema([
                                Forms\Components\Select::make('payment_status')
                                    ->label('Payment Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                        'partial' => 'Partial',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('status')
                                    ->label('Shipping Status')
                                    ->options(ShippingStatus::class)
                                    ->default('pickup')
                                    ->required()
                                    ->native(false),

                                Forms\Components\DateTimePicker::make('shipped_at')
                                    ->label('Shipped Date'),

                                Forms\Components\DatePicker::make('estimated_delivery_date')
                                    ->label('Est. Delivery'),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('')
                            ->schema([
                                Forms\Components\Placeholder::make('invoice_notice')
                                    ->content('Invoice will be generated automatically after saving.')
                                    ->columnSpanFull(),
                            ]),

                        // Hidden field to store receiver mode for processing
                        Forms\Components\Hidden::make('receiver_mode')->default('single'),
                    ]),
            ])
                ->columnSpanFull()
                ->skippable()
                ->persistStepInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shipping_reference')
                    ->label('Reference')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->copyable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Sender')
                    ->searchable()
                    ->badge(),

                Tables\Columns\TextColumn::make('client_existence')
                    ->label('Client')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'returning-client' => 'success',
                        'new-client' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'new-client' => 'New',
                        'returning-client' => 'Returning',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                Tables\Columns\TextColumn::make('tracking_number')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('origin_branch_id')
                    ->label('From')
                    ->icon('heroicon-m-arrow-up')
                    ->iconColor('danger')
                    ->searchable(),

                Tables\Columns\TextColumn::make('destination_branch_id')
                    ->label('To')
                    ->icon('heroicon-m-arrow-down')
                    ->iconColor('warning')
                    ->searchable(),

                Tables\Columns\TextColumn::make('shipped_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state) => '$' . number_format($state, 2)),

                Tables\Columns\TextColumn::make('paid')
                    ->label('Paid')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn($state) => '$' . number_format($state, 2)),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Shipment Status')
                    ->options(ShippingStatus::class),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid'    => 'Paid',
                    ]),

                Tables\Filters\SelectFilter::make('container_clearance')
                    ->label('Container Clearance')
                    ->options([
                        'cleared'     => 'Cleared',
                        'not_cleared' => 'Not Cleared / Pending',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'cleared' => $query->whereHas(
                                'containerStatus',
                                fn (Builder $q) => $q->where('is_cleared', true)
                            ),
                            'not_cleared' => $query->where(
                                fn (Builder $q) => $q
                                    ->whereDoesntHave('containerStatus')
                                    ->orWhereHas(
                                        'containerStatus',
                                        fn (Builder $inner) => $inner->where('is_cleared', false)
                                    )
                            ),
                            default => $query,
                        };
                    }),
            ])
            ->actions(
                [
                    ActionGroup::make([
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make(),

                        Tables\Actions\Action::make('shipping')
                            ->label('Update Status')
                            ->icon('heroicon-m-truck')
                            ->color('info')
                            ->modalSubmitActionLabel('Update')
                            ->fillForm(function ($record) {
                                return [
                                    'status' => $record->status->value,
                                    'shipped_at' => $record->shipped_at,
                                    'estimated_delivery_date' => $record->estimated_delivery_date,
                                    'statusupdate' => $record->statusupdate,
                                ];
                            })
                            ->form([
                                Forms\Components\Section::make('Update Status')
                                    ->schema([
                                        Forms\Components\TextInput::make('location')
                                            ->label('Location')
                                            ->required(),
                                        Forms\Components\Select::make('status')
                                            ->label('Status')
                                            ->options(ShippingStatus::class)
                                            ->required()
                                            ->native(false),
                                        Forms\Components\DateTimePicker::make('shipped_at')
                                            ->required(),
                                        Forms\Components\DatePicker::make('estimated_delivery_date')
                                            ->required(),
                                        Forms\Components\Textarea::make('remarks')
                                            ->label('Notes')
                                            ->columnSpanFull()
                                            ->required(),

                                        TableRepeater::make('statusupdate')
                                            ->label('History')
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->schema([
                                                Forms\Components\TextInput::make('location')->readOnly(),
                                                Forms\Components\Select::make('status')
                                                    ->options(ShippingStatus::class)
                                                    ->disabled(),
                                                Forms\Components\Textarea::make('remarks')->readOnly(),
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ])
                            ->action(function ($record, $data) {
                                $record->status = $data['status'];
                                $record->shipped_at = $data['shipped_at'];
                                $record->estimated_delivery_date = $data['estimated_delivery_date'];
                                $record->save();

                                ShipmentUpdate::create([
                                    'shipment_id' => $record->id,
                                    'location' => $data['location'],
                                    'status' => $data['status'],
                                    'remarks' => $data['remarks'],
                                ]);
                            }),

                        Tables\Actions\Action::make('receiver')
                            ->label('Receivers')
                            ->icon('heroicon-m-user')
                            ->color('gray')
                            ->fillForm(fn($record) => ['receivers' => $record->receivers])
                            ->infolist([
                                RepeatableEntry::make('receivers')
                                    ->label('')
                                    ->schema([
                                        TextEntry::make('receiver_name')->label('Name'),
                                        TextEntry::make('receiver_phone')->label('Phone'),
                                        TextEntry::make('receiver_email')->label('Email'),
                                        TextEntry::make('address'),
                                    ])
                                    ->columns(4),
                            ])
                            ->modalSubmitAction(false),

                        Tables\Actions\Action::make('items')
                            ->label('Items')
                            ->color('info')
                            ->icon('heroicon-m-cube')
                            ->modalWidth('5xl')
                            ->slideOver()
                            ->fillForm(fn($record) => ['items' => $record->items])
                            ->infolist([
                                RepeatableEntry::make('items')
                                    ->label('')
                                    ->schema([
                                        ImageEntry::make('product.product_image')->label('')->columnSpan(2),
                                        TextEntry::make('receiver.receiver_name')->badge()->label('Receiver'),
                                        TextEntry::make('product.name')->state(fn($record) => $record->product?->name . ' (' . $record->quantity . 'x)')->columnSpan(2)->label('Product'),
                                        TextEntry::make('item_cost')->label('Value')->formatStateUsing(fn($state) => '$' . number_format($state, 2)),
                                    ])
                                    ->columns(6),
                            ])
                            ->modalSubmitAction(false),

                        Tables\Actions\Action::make('Print Invoice')
                            ->icon('heroicon-m-document-text')
                            ->color('success')
                            ->url(fn($record) => route('shipping-invoice', $record->id), shouldOpenInNewTab: true),

                        Tables\Actions\Action::make('Download PDF')
                            ->icon('heroicon-m-arrow-down-tray')
                            ->color('primary')
                            ->url(fn($record) => route('shipping-invoice-pdf', $record->id), shouldOpenInNewTab: true),

                        Tables\Actions\Action::make('Receipt')
                            ->icon('heroicon-m-printer')
                            ->color('info')
                            ->url(fn($record) => route('shipping-receipt', $record), shouldOpenInNewTab: true),

                        // Tables\Actions\Action::make('Packing Slip')
                        //     ->color('warning')
                        //     ->icon('heroicon-m-inbox-stack')
                        //     ->url(fn($record) => route('packing-slip', $record->id), shouldOpenInNewTab: true),

                        Tables\Actions\Action::make('Print Label')
                            ->label('Print Shipping Label')
                            ->color('gray')
                            ->icon('heroicon-m-tag')
                            ->url(fn($record) => route('shipping-label', $record->id), shouldOpenInNewTab: true),

                        Tables\Actions\Action::make('payments')
                            ->label('Payments')
                            ->color('success')
                            ->icon('heroicon-m-banknotes')
                            ->visible(fn () =>Auth::user()?->hasAnyRole(['super_admin','CEO','Accountant']))
                            ->modalWidth('4xl')
                            ->fillForm(fn($record) => [
                                'total_amount' => $record->total,
                                'paid_amount' => $record->paid,
                                'balance' => $record->total - $record->paid,
                            ])
                            ->form([
                                Forms\Components\Section::make('Payment Summary')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Placeholder::make('total_display')
                                                    ->label('Total Amount')
                                                    ->content(fn($record) => '$' . number_format($record->total, 2))
                                                    ->extraAttributes(['class' => 'text-lg font-bold']),
                                                Forms\Components\Placeholder::make('paid_display')
                                                    ->label('Amount Paid')
                                                    ->content(fn($record) => '$' . number_format($record->paid, 2))
                                                    ->extraAttributes(['class' => 'text-lg font-bold text-success-600']),
                                                Forms\Components\Placeholder::make('balance_display')
                                                    ->label('Balance Due')
                                                    ->content(fn($record) => '$' . number_format($record->total - $record->paid, 2))
                                                    ->extraAttributes(['class' => 'text-lg font-bold text-danger-600']),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Payment History')
                                    ->schema([
                                        Forms\Components\Placeholder::make('payment_history')
                                            ->label('')
                                            ->content(function ($record) {
                                                if ($record->payments->isEmpty()) {
                                                    return 'No payments recorded yet.';
                                                }
                                                $html = '<div class="space-y-2">';
                                                foreach ($record->payments as $payment) {
                                                    $html .= '<div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-800 rounded">';
                                                    $html .= '<span class="font-medium">' . ($payment->paying_method ?? 'N/A') . '</span>';
                                                    $html .= '<span class="text-success-600 font-bold">$' . number_format($payment->amount, 2) . '</span>';
                                                    $html .= '<span class="text-gray-500 text-sm">' . ($payment->paid_on ? \Carbon\Carbon::parse($payment->paid_on)->format('M d, Y H:i') : 'N/A') . '</span>';
                                                    $html .= '</div>';
                                                }
                                                $html .= '</div>';
                                                return new \Illuminate\Support\HtmlString($html);
                                            }),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Add New Payment')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('new_paid_on')
                                                    ->label('Payment Date')
                                                    ->default(now())
                                                    ->required(),

                                                Forms\Components\Select::make('new_paying_method')
                                                    ->label('Payment Method')
                                                    ->options([
                                                        'CASH' => 'Cash',
                                                        'Zelle' => 'Zelle',
                                                        'Cash App' => 'Cash App',
                                                        'BANK TRANSFER' => 'Bank Transfer',
                                                        'CREDIT/DEBIT CARD' => 'Card',
                                                        'CHEQUE' => 'Cheque',
                                                        'PAYPAL' => 'PayPal',
                                                        'WAIVED' => 'Waived',
                                                    ])
                                                    ->required()
                                                    ->live()
                                                    ->native(false),

                                                Forms\Components\TextInput::make('new_amount')
                                                    ->label('Amount')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->required()
                                                    ->default(fn($record) => max(0, $record->total - $record->paid)),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('new_bankname')
                                                    ->label('Bank Name')
                                                    ->visible(fn($get): bool => $get('new_paying_method') === 'BANK TRANSFER'),

                                                Forms\Components\TextInput::make('new_accountnumber')
                                                    ->label('Account Number')
                                                    ->visible(fn($get): bool => $get('new_paying_method') === 'BANK TRANSFER'),

                                                Forms\Components\TextInput::make('new_cheque_no')
                                                    ->label('Cheque Number')
                                                    ->visible(fn($get): bool => $get('new_paying_method') === 'CHEQUE')
                                                    ->columnSpanFull(),
                                            ]),

                                        Forms\Components\Textarea::make('new_payment_note')
                                            ->label('Payment Notes')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->action(function ($record, array $data) {
                                // Create the new payment
                                if (!empty($data['new_amount']) && $data['new_amount'] > 0) {
                                    \App\Models\Payment::create([
                                        'branch_id' => Filament::getTenant()->id,
                                        'user_id' => auth()->id(),
                                        'shipment_id' => $record->id,
                                        'payment_type' => 'credit',
                                        'payment_ref' => 'PAY-' . strtoupper(bin2hex(random_bytes(4))),
                                        'paying_method' => $data['new_paying_method'],
                                        'amount' => $data['new_amount'],
                                        'paid_on' => $data['new_paid_on'],
                                        'bankname' => $data['new_bankname'] ?? null,
                                        'accountnumber' => $data['new_accountnumber'] ?? null,
                                        'cheque_no' => $data['new_cheque_no'] ?? null,
                                        'payment_note' => $data['new_payment_note'] ?? null,
                                        'change' => 0,
                                    ]);

                                    // Update shipment paid amount
                                    $totalPaid = $record->payments()->sum('amount');
                                    $record->paid = $totalPaid;

                                    // Update payment status
                                    if ($totalPaid >= $record->total) {
                                        $record->payment_status = 'paid';
                                    } elseif ($totalPaid > 0) {
                                        $record->payment_status = 'partial';
                                    } else {
                                        $record->payment_status = 'pending';
                                    }

                                    $record->save();

                                    // Update invoice status if exists
                                    if ($record->invoice) {
                                        $record->invoice->update([
                                            'status' => $record->payment_status,
                                        ]);
                                    }

                                    
                                    \Filament\Notifications\Notification::make()
                                        ->title('Payment Added')
                                        ->body('Payment of $' . number_format($data['new_amount'], 2) . ' has been recorded.')
                                        ->success()
                                        ->send();
                                }
                            })
                            ->modalSubmitActionLabel('Add Payment'),

                        // Tables\Actions\Action::make('send_invoice')
                        //     ->label('Email Invoice')
                        //     ->icon('heroicon-m-envelope')
                        //     ->color('gray')
                        //     ->requiresConfirmation()
                        //     ->modalHeading('Send Invoice')
                        //     ->modalDescription(fn($record) => "Send to {$record->client?->email}?")
                        //     ->action(function ($record) {
                        //         \App\Service\InvoiceService::sendInvoiceEmail($record);
                        //         \Filament\Notifications\Notification::make()
                        //             ->title('Invoice Sent')
                        //             ->success()
                        //             ->send();
                        //     }),

                        Tables\Actions\Action::make('send_message')
                            ->label('Send Message')
                            ->icon('heroicon-m-chat-bubble-left-right')
                            ->color('info')
                            ->visible(fn () =>Auth::user()?->hasAnyRole(['super_admin','CEO']))
                            ->modalHeading('Send Message to Client')
                            ->form([
                                Forms\Components\Select::make('template_id')
                                    ->label('Use Template')
                                    ->options(\App\Models\MessageTemplate::where('is_active', true)->pluck('name', 'id'))
                                    ->placeholder('Select a template or write custom message')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $template = \App\Models\MessageTemplate::find($state);
                                            if ($template) {
                                                $set('subject', $template->subject);
                                                $set('body', $template->body);
                                                $set('channel', $template->type);
                                            }
                                        }
                                    }),

                                Forms\Components\TextInput::make('subject')
                                    ->label('Subject')
                                    ->required()
                                    ->placeholder('Message subject'),

                                Forms\Components\RichEditor::make('body')
                                    ->label('Message')
                                    ->required()
                                    ->placeholder('Write your message...'),

                                Forms\Components\Select::make('channel')
                                    ->label('Send Via')
                                    ->options([
                                        'email' => 'Email Only',
                                        'sms' => 'SMS Only',
                                        'both' => 'Both Email & SMS',
                                    ])
                                    ->default('email')
                                    ->required()
                                    ->native(false),
                            ])
                            ->action(function ($record, array $data) {
                                \App\Service\ShipmentMessageService::sendCustomMessage(
                                    $data['subject'],
                                    $data['body'],
                                    $data['channel'],
                                    'shipment',
                                    null,
                                    $record->id
                                );
                                \Filament\Notifications\Notification::make()
                                    ->title('Message Sent')
                                    ->success()
                                    ->send();
                            }),

                        Tables\Actions\Action::make('media_evidence')
                            ->label('Media / Evidence')
                            ->icon('heroicon-m-camera')
                            ->color('purple')
                            ->modalWidth('5xl')
                            ->modalHeading('Shipment Media & Evidence')
                            ->form([
                                Forms\Components\Section::make('Existing Media')
                                    ->schema([
                                        Forms\Components\Placeholder::make('media_gallery')
                                            ->label('')
                                            ->content(function ($record) {
                                                $media = $record->media()->latest()->get();
                                                if ($media->isEmpty()) {
                                                    return 'No media uploaded yet.';
                                                }
                                                $html = '<div class="grid grid-cols-3 gap-4">';
                                                foreach ($media as $item) {
                                                    $stageLabel = ShipmentMedia::STAGES[$item->stage] ?? $item->stage;
                                                    $html .= '<div class="border rounded-lg p-2 dark:border-gray-700">';
                                                    if ($item->type === 'image') {
                                                        $html .= '<img src="' . asset('storage/' . $item->file_path) . '" class="w-full h-32 object-cover rounded mb-2" />';
                                                    } else {
                                                        $html .= '<div class="w-full h-32 bg-gray-100 dark:bg-gray-800 rounded mb-2 flex items-center justify-center"><span class="text-2xl">🎥</span></div>';
                                                    }
                                                    $html .= '<span class="text-xs font-medium">' . $stageLabel . '</span>';
                                                    if ($item->caption) {
                                                        $html .= '<p class="text-xs text-gray-500 mt-1">' . e($item->caption) . '</p>';
                                                    }
                                                    $html .= '</div>';
                                                }
                                                $html .= '</div>';
                                                return new \Illuminate\Support\HtmlString($html);
                                            }),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Upload New Media')
                                    ->schema([
                                        Forms\Components\Select::make('media_stage')
                                            ->label('Stage')
                                            ->options(ShipmentMedia::STAGES)
                                            ->required()
                                            ->native(false),

                                        Forms\Components\FileUpload::make('media_files')
                                            ->label('Upload Images / Videos')
                                            ->multiple()
                                            ->directory('shipment-media')
                                            ->acceptedFileTypes(['image/*', 'video/*'])
                                            ->maxSize(51200) // 50MB
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('media_caption')
                                            ->label('Caption (optional)')
                                            ->placeholder('Describe these files...'),
                                    ])
                                    ->columns(2),
                            ])
                            ->action(function ($record, array $data) {
                                if (!empty($data['media_files'])) {
                                    foreach ($data['media_files'] as $filePath) {
                                        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                        $type = in_array($extension, ['mp4', 'mov', 'avi', 'webm', 'mkv']) ? 'video' : 'image';

                                        ShipmentMedia::create([
                                            'shipment_id' => $record->id,
                                            'type' => $type,
                                            'file_path' => $filePath,
                                            'stage' => $data['media_stage'],
                                            'caption' => $data['media_caption'] ?? null,
                                            'uploaded_by' => auth()->id(),
                                        ]);
                                    }

                                    \Filament\Notifications\Notification::make()
                                        ->title('Media Uploaded')
                                        ->body(count($data['media_files']) . ' file(s) uploaded successfully.')
                                        ->success()
                                        ->send();
                                }
                            })
                            ->modalSubmitActionLabel('Upload'),

                        Tables\Actions\Action::make('complaint_link')
                            ->label('Complaint Link')
                            ->icon('heroicon-m-exclamation-triangle')
                            ->color('danger')
                            ->action(function ($record) {
                                $token = CustomerFeedback::generateComplaintToken();

                                CustomerFeedback::create([
                                    'complaint_token' => $token,
                                    'type' => 'complaint',
                                    'shipment_id' => $record->id,
                                    'feedback_on' => 'Rose Shipment',
                                    'customer_name' => $record->client?->name ?? 'Pending',
                                    'customer_email' => $record->client?->email ?? '',
                                    'customer_phone' => $record->client?->phone ?? '',
                                    'category' => 'Other',
                                    'rating' => 3,
                                    'status' => 'pending',
                                    'priority' => 'normal',
                                ]);

                                $url = url('/complaint/' . $token);

                                \Filament\Notifications\Notification::make()
                                    ->title('Complaint Link Generated')
                                    ->body($url)
                                    ->success()
                                    ->persistent()
                                    ->send();
                            }),

                        Tables\Actions\Action::make('copy_external_link')
                            ->label('Client Form Link')
                            ->icon('heroicon-m-link')
                            ->color('warning')
                            ->action(function ($record) {
                                $url = route('external-shipment-form', $record->external_token);
                                \Filament\Notifications\Notification::make()
                                    ->title('Link Copied!')
                                    ->body($url)
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn($record) => !$record->external_form_completed),
                    ]),
                ],
                position: ActionsPosition::BeforeColumns,
            )
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->groups([
                Tables\Grouping\Group::make('container_number')
                    ->label('Container')
                    ->getTitleFromRecordUsing(function (Shipment $record): string {
                        $cn      = $record->container_number;
                        $cleared = $record->containerStatus?->is_cleared;

                        if (! $cn) {
                            return 'No Container';
                        }

                        return $cleared
                            ? "CON{$cn}   ✓  Cleared"
                            : "CON{$cn}   ⏳  Pending Clearance";
                    })
                    ->getDescriptionFromRecordUsing(function (Shipment $record): string {
                        $review = $record->containerStatus?->review;
                        return $review ? "Note: {$review}" : '';
                    })
                    ->collapsible()
                    ->orderQueryUsing(
                        fn (Builder $query, string $direction) =>
                            $query->orderBy('container_number', $direction)
                    ),
            ])
            ->defaultGroup('container_number')
            ->groupingSettingsInDropdownOnDesktop()
            ->recordClasses(function (Shipment $record): string {
                return $record->containerStatus?->is_cleared ? 'container-cleared' : '';
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
        ];
    }
}

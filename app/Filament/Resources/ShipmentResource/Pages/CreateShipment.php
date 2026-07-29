<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\ShippingStatus;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ShipmentResource;
use App\Models\City;
use App\Models\Client;
use App\Models\Country;
use App\Models\Invoice;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentUpdate;
use App\Models\State;
use App\Service\InvoiceService;
use App\Service\NotificationService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Wizard;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Concurrency;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CreateShipment extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = ShipmentResource::class;

    protected function getSteps(): array
    {
        return self::formSteps();
    }

    /**
     * Helper method to get item schema based on device
     */
    protected static function getItemSchema(): array
    {
        $isMobile = request()->header('User-Agent') &&
                    preg_match('/Mobile|Android|iPhone|iPad/i', request()->header('User-Agent'));

        $schema = [
            Forms\Components\Select::make('product_id')
                ->label('Product')
                ->required()
                ->relationship('product', 'name')
                ->preload()
                ->searchable()
                ->createOptionForm(ProductResource::productself())
                ->editOptionForm(ProductResource::productself()),

            Forms\Components\TextInput::make('quantity')
                ->label('Qty')
                ->required()
                ->default(1)
                ->numeric()
                ->minValue(1)
                ->live(onBlur: true),

            Forms\Components\TextInput::make('item_cost')
                ->required()
                ->label('Value ($)')
                ->default(0)
                ->numeric()
                ->prefix('$')
                ->live(onBlur: true),
        ];

        return $schema;
    }

    /**
     * Create repeater component based on screen size
     */
    protected static function createItemsRepeater(string $name = 'pickupitems', ?string $relationship = null): Forms\Components\Repeater|TableRepeater
    {
        // Check if request is from mobile device
        $isMobile = request()->header('User-Agent') &&
                    preg_match('/Mobile|Android|iPhone|iPad/i', request()->header('User-Agent'));

        $col = $isMobile ? 1 : 3;

        $repeater = Forms\Components\Repeater::make($name)
            ->label('')
            ->live()
            ->addActionLabel('+ Add More Item')
            ->defaultItems(1)
            ->itemLabel(fn (array $state): ?string => isset($state['product_id']) ? 'Item #' : 'New Item'
            )
            ->schema(self::getItemSchema())
            ->columns($col);

        if ($relationship) {
            $repeater->relationship($relationship);
        }

        return $repeater;
    }

    public static function formSteps(): array
    {
        return [
            // Step 1: Client/Sender Information
            Wizard\Step::make('Sender Info')
                ->icon('heroicon-o-user')
                ->description('Client & route details')
                ->schema([
                    Forms\Components\Section::make('Shipment Type & Reference')
                        ->schema([
                            Forms\Components\Select::make('shipment_type')
                                ->label('Shipment Type')
                                ->options([
                                    'new' => 'New Shipment (New Container)',
                                    'existing' => 'Existing Shipment (Same Container)',
                                ])
                                ->default('new')
                                ->required()
                                ->live()
                                ->dehydrated(false)
                                ->native(false)
                                ->visible(fn (string $operation): bool => $operation === 'create')
                                ->afterStateUpdated(fn (callable $set) => $set('existing_shipment_id', null))
                                ->columnSpan(1),

                            Forms\Components\Select::make('existing_shipment_id')
                                ->label('Select Existing Container/Shipment')
                                ->placeholder('Choose an existing container to add to')
                                ->options(function () {
                                    // Get recent shipments with their container info, grouped by container
                                    return \App\Models\Shipment::whereNotNull('container_number')
                                        ->whereNotNull('shipping_reference')
                                        ->orderBy('created_at', 'desc')
                                        ->limit(100)
                                        ->get()
                                        ->mapWithKeys(fn ($shipment) => [
                                            $shipment->id => $shipment->shipping_reference,
                                        ]);
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->dehydrated(false)
                                ->visible(fn ($get, string $operation): bool => $operation === 'create' && $get('shipment_type') === 'existing'
                                )
                                ->required(fn ($get): bool => $get('shipment_type') === 'existing')
                                ->helperText('Select the container/shipment you want to add this client to')
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('shipping_reference')
                                ->label('Shipping Reference')
                                ->placeholder('Auto-generated on save')
                                ->disabled(fn (string $operation): bool => $operation === 'create')
                                ->dehydrated()
                                // ->helperText(fn (string $operation): string => $operation === 'create'
                                //     ? 'Will be generated from shipment date after saving'
                                //     : 'You can edit this reference if needed')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Client Details')
                        ->schema([
                            Forms\Components\Select::make('client_id')
                                ->label('Select Client (Sender)')
                                ->required()
                                ->relationship(
                                    name: 'client',
                                    titleAttribute: 'name'
                                )
                                ->searchable(['name', 'email', 'phone'])
                                ->createOptionForm(ClientResource::clientschema())
                                ->editOptionForm(ClientResource::clientschema())
                                ->preload()
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
                            Forms\Components\Hidden::make('container_number'),
                            Forms\Components\Hidden::make('client_sequence'),
                            Forms\Components\Hidden::make('total_shipment_sequence'),
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
                                ->default('Ghana')
                                ->searchable()
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
                    // Receiver Mode Selection
                    // Forms\Components\Section::make('How many receivers?')
                    //     ->description('Select based on your shipment needs')
                    //     ->schema([
                    //         Forms\Components\Radio::make('receiver_mode')
                    //             ->label('')
                    //             ->options([
                    //                 'single' => 'Single Receiver - All items go to one person (fastest)',
                    //                 'multiple' => 'Multiple Receivers - Items go to different people',
                    //             ])
                    //             ->default('single')
                    //             ->live()
                    //             ->inline()
                    //             ->columnSpanFull(),
                    //     ])
                    //     ->compact(),
                    Hidden::make('receiver_mode')->default('multiple'),

                    // SINGLE RECEIVER MODE
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
                                        ->hidden()
                                        ->placeholder('email@example.com'),

                                    Forms\Components\Select::make('single_receiver_id_type')
                                        ->label('ID Type')
                                        ->options([
                                            'Drivers License' => 'Drivers License',
                                            'Ghana Card' => 'Ghana Card',
                                            'Voter ID Card' => 'Voter ID Card',
                                            'Passport' => 'Passport',
                                        ])
                                        ->hidden()
                                        ->native(false),

                                    Forms\Components\TextInput::make('single_receiver_id_number')
                                        ->label('ID Number')
                                        ->hidden(),

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
                                ->hidden()
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->visible(fn ($get) => $get('receiver_mode') === 'single'),

                    // Items Section - For Single Receiver
                    Forms\Components\Section::make('Pickup Items')
                        ->description('Add all items - they will be assigned to the receiver above')
                        ->schema([
                            self::createItemsRepeater('pickupitems', 'pickupitems'),

                            // Summary
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('item_count')
                                        ->content(fn ($get) => 'Items: '.count($get('pickupitems') ?? []))
                                        ->live()
                                        ->label(''),

                                    Forms\Components\Placeholder::make('qty_total')
                                        ->content(function ($get) {
                                            $items = $get('pickupitems') ?? [];

                                            return 'Qty: '.collect($items)
                                                ->sum(fn ($item) => (int) ($item['quantity'] ?? 0));
                                        })
                                        ->live()
                                        ->label(''),

                                    Forms\Components\Placeholder::make('cost_total')
                                        ->content(function ($get, $set) {
                                            $items = $get('pickupitems') ?? [];
                                            $subtotal = collect($items)
                                                ->sum(fn ($item) => (float) ($item['item_cost'] ?? 0));

                                            $discount = (float) ($get('discount') ?? 0);
                                            $insurance = (float) ($get('insurance') ?? 0);
                                            $total = max($subtotal - $discount + $insurance, 0);

                                            $set('shipping_cost', $subtotal);
                                            $set('total', $total);

                                            return 'Total: $'.number_format($subtotal, 2);
                                        })
                                        ->live()
                                        ->label(''),
                                ]),
                        ])
                        ->visible(fn ($get) => $get('receiver_mode') === 'single'),

                    // MULTIPLE RECEIVERS MODE
                    Forms\Components\Section::make('Receivers & Their Items')
                        ->description('Add receivers and assign items to each')
                        ->schema([
                            Forms\Components\Repeater::make('receivers')
                                ->relationship('receivers')
                                ->label('')
                                ->addActionLabel('+ Add More Receiver')
                                ->defaultItems(1)
                                ->cloneable()
                                ->live()
                                ->itemLabel(fn (array $state): ?string => $state['receiver_name'] ?? 'New Receiver')
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

                                    // Previous Receiver Selection
                                    Forms\Components\Select::make('previous_receiver')
                                        ->label('Use Previous Receiver')
                                        ->placeholder('Select from previous receivers or enter new')
                                        ->options(function (callable $get) {
                                            $clientId = $get('../../client_id');
                                            if (! $clientId) {
                                                return [];
                                            }

                                            return Receiver::whereHas('shipment', function ($query) use ($clientId) {
                                                $query->where('client_id', $clientId);
                                            })
                                                ->get()
                                                ->unique(fn ($r) => strtolower($r->receiver_name).'-'.$r->receiver_phone)
                                                ->mapWithKeys(fn ($r) => [
                                                    $r->id => $r->receiver_name.($r->receiver_phone ? " ({$r->receiver_phone})" : ''),
                                                ]);
                                        })
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                $receiver = Receiver::find($state);
                                                if ($receiver) {
                                                    $set('receiver_name', $receiver->receiver_name);
                                                    $set('receiver_phone', $receiver->receiver_phone);
                                                    $set('receiver_email', $receiver->receiver_email);
                                                    $set('country', $receiver->country);
                                                    $set('state_region', $receiver->state_region);
                                                    $set('city', $receiver->city);
                                                    $set('address', $receiver->address);
                                                    $set('receiver_id_type', $receiver->receiver_id_type);
                                                    $set('receiver_id_number', $receiver->receiver_id_number);
                                                }
                                            }
                                        })
                                        ->dehydrated(false)
                                        ->columnSpanFull()
                                        ->helperText('Auto-populate from a previous shipment or enter new receiver details below'),

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
                                                ->label('Email')
                                                ->hidden(),

                                            Forms\Components\Select::make('receiver_id_type')
                                                ->label('ID Type')
                                                ->options([
                                                    'Drivers License' => 'Drivers License',
                                                    'Ghana Card' => 'Ghana Card',
                                                    'Voter ID Card' => 'Voter ID Card',
                                                    'Passport' => 'Passport',
                                                ])
                                                ->hidden()
                                                ->native(false),

                                            Forms\Components\TextInput::make('receiver_id_number')
                                                ->label('ID Number')
                                                ->hidden(),

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
                                        ->hidden()
                                        ->columnSpanFull(),

                                    Forms\Components\Section::make('Items for this Receiver')
                                        ->schema([
                                            self::createItemsRepeater('items', 'items')
                                                ->schema([
                                                    Forms\Components\Hidden::make('box_no')->default(null),
                                                    ...self::getItemSchema(),
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
                                            $receivers = $get('receivers') ?? [];
                                            $totalItems = 0;
                                            foreach ($receivers as $receiver) {
                                                if (isset($receiver['items']) && is_array($receiver['items'])) {
                                                    $totalItems += count($receiver['items']);
                                                }
                                            }

                                            return 'Items: '.$totalItems;
                                        })
                                        ->live()
                                        ->label(''),

                                    Forms\Components\Placeholder::make('multi_qty_total')
                                        ->content(function ($get) {
                                            $receivers = $get('receivers') ?? [];
                                            $totqty = 0;
                                            foreach ($receivers as $receiver) {
                                                if (isset($receiver['items']) && is_array($receiver['items'])) {
                                                    foreach ($receiver['items'] as $item) {
                                                        $totqty += (int) ($item['quantity'] ?? 0);
                                                    }
                                                }
                                            }

                                            return 'Qty: '.$totqty;
                                        })
                                        ->live()
                                        ->label(''),

                                    Forms\Components\Placeholder::make('multi_cost_total')
                                        ->content(function ($get, $set) {
                                            $receivers = $get('receivers') ?? [];
                                            $subtotal = 0;
                                            foreach ($receivers as $receiver) {
                                                if (isset($receiver['items']) && is_array($receiver['items'])) {
                                                    foreach ($receiver['items'] as $item) {
                                                        $subtotal += (float) ($item['item_cost'] ?? 0);
                                                    }
                                                }
                                            }

                                            $discount = (float) ($get('discount') ?? 0);
                                            $insurance = (float) ($get('insurance') ?? 0);
                                            $total = max($subtotal - $discount + $insurance, 0);

                                            $set('shipping_cost', $subtotal);
                                            $set('total', $total);

                                            return 'Total: $'.number_format($subtotal, 2);
                                        })
                                        ->live()
                                        ->label(''),
                                ]),
                        ]),
                    // ->visible(fn($get) => $get('receiver_mode') === 'multiple'),
                ]),

            // Step 3: Payment
            Wizard\Step::make('Payment')
                ->icon('heroicon-o-banknotes')
                ->description('Record payments')
                ->schema([
                    Forms\Components\Section::make('Amount Summary')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('subtotal_summary')
                                        ->label('Subtotal')
                                        ->content(fn ($get) => '$'.number_format((float) ($get('shipping_cost') ?? 0), 2)),

                                    Forms\Components\Placeholder::make('total_summary')
                                        ->label('Total Due')
                                        ->content(fn ($get) => '$'.number_format((float) ($get('total') ?? 0), 2))
                                        ->extraAttributes(['class' => 'text-lg font-bold']),

                                    Forms\Components\Placeholder::make('payments_summary')
                                        ->label('Payments Added')
                                        ->content(function ($get) {
                                            $payments = $get('Payments') ?? [];
                                            $totalPayments = collect($payments)->sum(fn ($p) => (float) ($p['amount'] ?? 0));

                                            return '$'.number_format($totalPayments, 2);
                                        })
                                        ->live()
                                        ->extraAttributes(['class' => 'text-success-600 font-bold']),
                                ]),
                        ])
                        ->collapsible(),

                    Forms\Components\Section::make('Payment Records')
                        ->description('Add payments received for this shipment (optional - you can add payments later)')
                        ->schema([
                            Forms\Components\Repeater::make('Payments')
                                ->label('')
                                ->relationship('payments')
                                ->addActionLabel('+ Add Payment')
                                ->defaultItems(0)
                                ->live()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => isset($state['paying_method']) && isset($state['amount'])
                                        ? $state['paying_method'].' - $'.number_format((float) $state['amount'], 2)
                                        : 'New Payment'
                                )
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Hidden::make('branch_id')->default(Filament::getTenant()->id),
                                            Forms\Components\Hidden::make('currency')->default('USD'),

                                            Forms\Components\DateTimePicker::make('paid_on')
                                                ->label('Payment Date')
                                                ->default(now())
                                                ->required(),

                                            Forms\Components\Select::make('paying_method')
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
                                                ->live()
                                                ->required()
                                                ->native(false),

                                            Forms\Components\TextInput::make('amount_usd')
                                                ->label('Amount (USD)')
                                                ->numeric()
                                                ->prefix('$')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $exchangeRate = (float) ($get('exchange_rate') ?? 0);
                                                    if ($state && $exchangeRate > 0) {
                                                        $set('amount_ghs', round($state * $exchangeRate, 2));
                                                    }
                                                    // Keep backward compatibility with 'amount' field
                                                    $set('amount', $state);
                                                })
                                                ->required(),
                                        ]),

                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('exchange_rate')
                                                ->label('Exchange Rate (USD to GHS)')
                                                ->numeric()
                                                ->default(function () {
                                                    try {
                                                        $service = app(\App\Service\ExchangeRateService::class);

                                                        return $service->getCurrentRate('USD', 'GHS');
                                                    } catch (\Exception $e) {
                                                        return 12.0;
                                                    }
                                                })
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $amountUsd = (float) ($get('amount_usd') ?? 0);
                                                    if ($amountUsd && $state) {
                                                        $set('amount_ghs', round($amountUsd * $state, 2));
                                                    }
                                                })
                                                ->helperText('Current exchange rate')
                                                ->suffix('GHS per USD')
                                                ->required(),

                                            Forms\Components\TextInput::make('amount_ghs')
                                                ->label('Amount (GHS)')
                                                ->numeric()
                                                ->prefix('GH₵')
                                                ->disabled()
                                                ->dehydrated()
                                                ->helperText('Calculated automatically'),

                                            Forms\Components\Hidden::make('amount')
                                                ->default(fn ($get) => $get('amount_usd')),
                                        ]),

                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('bankname')
                                                ->label('Bank Name')
                                                ->visible(fn ($get): bool => $get('paying_method') === 'BANK TRANSFER'),

                                            Forms\Components\TextInput::make('accountnumber')
                                                ->label('Account Number')
                                                ->visible(fn ($get): bool => $get('paying_method') === 'BANK TRANSFER'),

                                            Forms\Components\TextInput::make('cheque_no')
                                                ->label('Cheque Number')
                                                ->visible(fn ($get): bool => $get('paying_method') === 'CHEQUE')
                                                ->columnSpanFull(),
                                        ]),

                                    Forms\Components\Hidden::make('user_id')->default(fn () => FacadesAuth::user()->id),
                                    Forms\Components\Hidden::make('change')->default(0),
                                    Forms\Components\Hidden::make('payment_ref')->default(fn () => 'PAY-'.strtoupper(bin2hex(random_bytes(4)))),
                                    Forms\Components\Hidden::make('payment_type')->default('credit'),

                                    Forms\Components\Textarea::make('payment_note')
                                        ->label('Payment Notes')
                                        ->rows(1)
                                        ->columnSpanFull(),
                                ]),

                            // Payment totals
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Placeholder::make('payment_total')
                                        ->label('Total Payments')
                                        ->content(function ($get) {
                                            $payments = $get('Payments') ?? [];
                                            $totalPayments = collect($payments)->sum(fn ($p) => (float) ($p['amount'] ?? 0));

                                            return '$'.number_format($totalPayments, 2);
                                        })
                                        ->live(),

                                    Forms\Components\Placeholder::make('balance_due')
                                        ->label('Balance Due')
                                        ->content(function ($get) {
                                            $total = (float) ($get('total') ?? 0);
                                            $payments = $get('Payments') ?? [];
                                            $totalPayments = collect($payments)->sum(fn ($p) => (float) ($p['amount'] ?? 0));
                                            $balance = $total - $totalPayments;

                                            return '$'.number_format($balance, 2);
                                        })
                                        ->live()
                                        ->extraAttributes(['class' => 'text-lg font-bold']),
                                ]),
                        ]),
                ]),

            // Step 4: Review & Complete
            Wizard\Step::make('Complete')
                ->icon('heroicon-o-check-circle')
                ->description('Review & save')
                ->schema([
                    Forms\Components\Section::make('Insurance')
                        ->schema([
                            Forms\Components\Toggle::make('insurance_accepted')
                                ->label('Client Accepts Insurance')
                                ->helperText('Does the client want their items to be insured?')
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (! $state) {
                                        $set('insurance', 0);
                                    }
                                })
                                ->default(false),

                            Forms\Components\TextInput::make('insurance')
                                ->label('Insurance Amount')
                                ->prefix('$')
                                ->numeric()
                                ->live(onBlur: true)
                                ->visible(fn ($get) => $get('insurance_accepted'))
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $subtotal = (float) ($get('shipping_cost') ?? 0);
                                    $discount = (float) ($get('discount') ?? 0);
                                    $insurance = (float) ($state ?? 0);
                                    $vat = (float) ($get('vat') ?? 0);
                                    $total = max($subtotal - $discount + $insurance + $vat, 0);
                                    $set('total', $total);
                                })
                                ->default(0),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Cost Summary')
                        ->schema([
                            Forms\Components\TextInput::make('shipping_cost')
                                ->label('Subtotal')
                                ->prefix('$')
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $subtotal = (float) ($state ?? 0);
                                    $discount = (float) ($get('discount') ?? 0);
                                    $insurance = (float) ($get('insurance') ?? 0);
                                    $vat = (float) ($get('vat') ?? 0);
                                    $total = max($subtotal - $discount + $insurance + $vat, 0);
                                    $set('total', $total);
                                })
                                ->default(0)
                                // ->disabled()
                                ->dehydrated(),

                            Forms\Components\TextInput::make('discount')
                                ->label('Discount')
                                ->prefix('$')
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $subtotal = (float) ($get('shipping_cost') ?? 0);
                                    $discount = (float) ($state ?? 0);
                                    $insurance = (float) ($get('insurance') ?? 0);
                                    $vat = (float) ($get('vat') ?? 0);
                                    $total = max($subtotal - $discount + $insurance + $vat, 0);
                                    $set('total', $total);
                                })
                                ->default(0),

                            Forms\Components\TextInput::make('vat_percentage')
                                ->label('VAT %')
                                ->suffix('%')
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $subtotal = (float) ($get('shipping_cost') ?? 0);
                                    $vatPercentage = (float) ($state ?? 0);
                                    $vat = $subtotal * ($vatPercentage / 100);
                                    $set('vat', $vat);

                                    $discount = (float) ($get('discount') ?? 0);
                                    $insurance = (float) ($get('insurance') ?? 0);
                                    $total = max($subtotal - $discount + $insurance + $vat, 0);
                                    $set('total', $total);
                                })
                                ->default(0),

                            Forms\Components\TextInput::make('vat')
                                ->label('VAT Amount')
                                ->prefix('$')
                                ->numeric()
                               // ->readOnly()
                                ->dehydrated()
                                ->live()
                                ->default(0),

                            Forms\Components\TextInput::make('total')
                                ->label('Grand Total')
                                ->prefix('$')
                                ->numeric()
                               // ->readOnly()
                                ->dehydrated()
                                ->live()
                                ->default(0)
                                ->extraAttributes(['class' => 'font-bold text-xl']),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Client Note')
                        ->schema([
                            Forms\Components\Textarea::make('client_note')
                                ->label('Special Note from Client')
                                ->helperText('Any special instructions or notes from the client')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Section::make('Status')
                        ->schema([

                            Forms\Components\DateTimePicker::make('shipped_at')
                                ->label('Est. Shipping Date')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set, callable $get, string $operation, $record) {
                                    if ($operation === 'edit' && $state && $record) {
                                        // Get current reference to preserve container number
                                        $currentRef = $get('shipping_reference');
                                        $containerNumber = $record->container_number ?? 1;

                                        // Extract container number from current reference if not in DB (format: CON{num}-...)
                                        if (! $containerNumber && $currentRef && preg_match('/^CON(\d+)-/', $currentRef, $matches)) {
                                            $containerNumber = (int) $matches[1];
                                        }

                                        // Regenerate reference with new date, excluding current shipment from counts
                                        $refData = \App\Models\Shipment::generateShippingReference('existing', $state, $record->id);

                                        // Build new reference with original container number
                                        // Format: CON{container}-{2-digit year}-{client seq 2 digits}-{shipment seq 3 digits}
                                        $date = \Carbon\Carbon::parse($state);
                                        $newReference = sprintf(
                                            'CON%d-%s-%02d-%03d',
                                            $containerNumber,
                                            $date->format('y'),
                                            $refData['client_sequence'],
                                            $refData['total_shipment_sequence']
                                        );

                                        $set('shipping_reference', $newReference);
                                    }
                                }),

                            Forms\Components\Hidden::make('payment_status')
                                ->default('pending'),

                            Forms\Components\Select::make('status')
                                ->label('Shipping Status')
                                ->options(ShippingStatus::class)
                                ->default('pickup')
                                ->required()
                                ->native(false),

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

                    Forms\Components\Hidden::make('receiver_mode')->default('single'),
                ]),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = Auth::id();
        $data['tracking_number'] = \App\Models\Shipment::generateTrackingNumber();
        $data['external_token'] = \App\Models\Shipment::generateExternalToken();

        // Generate shipping reference using the shipped_at date
        $shipmentType = $this->data['shipment_type'] ?? 'new';
        $shippedAt = $data['shipped_at'] ?? now();
        $existingShipmentId = $this->data['existing_shipment_id'] ?? null;

        $refData = \App\Models\Shipment::generateShippingReference(
            $shipmentType,
            $shippedAt,
            null, // excludeShipmentId (not editing)
            $existingShipmentId // existing shipment to use for container reference
        );

        $data['shipping_reference'] = $refData['reference'];
        $data['container_number'] = $refData['container_number'];
        $data['client_sequence'] = $refData['client_sequence'];
        $data['total_shipment_sequence'] = $refData['total_shipment_sequence'];

        unset($data['single_receiver_name']);
        unset($data['single_receiver_phone']);
        unset($data['single_receiver_email']);
        unset($data['single_receiver_id_type']);
        unset($data['single_receiver_id_number']);
        unset($data['single_receiver_country']);
        unset($data['single_receiver_state']);
        unset($data['single_receiver_city']);
        unset($data['single_receiver_address']);
        unset($data['receiver_mode']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $shipment = $this->getRecord();
        $formData = $this->data;

        $receiverMode = $formData['receiver_mode'] ?? 'single';

        if ($receiverMode === 'single' && ! empty($formData['single_receiver_name'])) {
            $receiver = Receiver::create([
                'shipment_id' => $shipment->id,
                'receiver_name' => $formData['single_receiver_name'],
                'receiver_phone' => $formData['single_receiver_phone'] ?? null,
                'receiver_email' => $formData['single_receiver_email'] ?? null,
                'receiver_id_type' => $formData['single_receiver_id_type'] ?? null,
                'receiver_id_number' => $formData['single_receiver_id_number'] ?? null,
                'country' => $formData['single_receiver_country'] ?? null,
                'state_region' => $formData['single_receiver_state'] ?? null,
                'city' => $formData['single_receiver_city'] ?? null,
                'address' => $formData['single_receiver_address'] ?? null,
            ]);

            $pickupItems = $shipment->pickupitems;
            foreach ($pickupItems as $pickupItem) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'receiver_id' => $receiver->id,
                    'product_id' => $pickupItem->product_id,
                    'quantity' => $pickupItem->quantity,
                    'item_cost' => $pickupItem->item_cost,
                    'box_no' => null,
                ]);
            }
        }

        $amountopay = $shipment->total;
        $paid = $shipment->payments->sum('amount');

        $shipment->total = $amountopay;
        $shipment->paid = $paid;

        // Update payment status based on payments
        if ($paid >= $amountopay && $amountopay > 0) {
            $shipment->payment_status = 'paid';
        } elseif ($paid > 0) {
            $shipment->payment_status = 'partial';
        } else {
            $shipment->payment_status = 'pending';
        }

        $shipment->save();

        $left = $amountopay - $paid;

        // Determine invoice status
        $invoiceStatus = 'pending';
        if ($paid >= $amountopay && $amountopay > 0) {
            $invoiceStatus = 'paid';
        } elseif ($paid > 0) {
            $invoiceStatus = 'partial';
        }

        Invoice::create([
            'shipment_id' => $shipment->id,
            'total_amount' => $shipment->total,
            'status' => $invoiceStatus,
        ]);

        ShipmentUpdate::create([
            'shipment_id' => $shipment->id,
            'location' => $shipment->origin_branch_id ?? 'Origin',
            'status' => $shipment->status->value,
            'remarks' => 'Shipment created - '.ucfirst($shipment->status->value),
        ]);

        $message = $this->buildNotificationMessage($shipment);

        $email = $shipment->client?->email;
        $phone = $shipment->client?->phone;

        if ($email || $phone) {
            Concurrency::defer([
                fn () => $phone ? NotificationService::sendSmsToSender($phone, $message) : null,
                fn () => $email ? $this->sendInvoiceToClient($shipment) : null,
            ]);
        }

    }

    protected function getCreatedNotification(): ?Notification
    {
        $shipment = $this->getRecord();

        return Notification::make()
            ->title('Shipment Created!')
            ->body("Tracking: {$shipment->tracking_number} | Ref: {$shipment->shipping_reference}")
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('download_pdf')
                    ->label('View Invoice')
                    ->url(route('shipping-invoice-pdf', $shipment->id))
                    ->openUrlInNewTab(),
                \Filament\Notifications\Actions\Action::make('print_label')
                    ->label('Print Shipping Label')
                    ->url(route('shipping-label', $shipment->id))
                    ->openUrlInNewTab()
                    ->color('warning'),
            ])
            ->persistent()
            ->send();
    }

    protected function buildNotificationMessage($shipment): string
    {
        $clientName = $shipment->client?->name ?? 'Customer';
        $trackingNumber = $shipment->tracking_number;
        $total = number_format($shipment->total, 2);
        $from = $shipment->origin_branch_id;
        $to = $shipment->destination_branch_id;
        $url = route('public-shipment-view', $shipment->public_view_token);

        return "Dear {$clientName}, your shipment has been created. ".
            "Tracking: {$trackingNumber}. ".
            "Route: {$from} to {$to}. ".
            "Total: \${$total}. ".
            "View Details: {$url}. ".
            'Thank you for choosing Rose Door To Door Shipping!';
    }

    protected function sendInvoiceToClient($shipment): void
    {
        try {
            InvoiceService::sendInvoiceEmail($shipment);
            logger()->info("Invoice sent to {$shipment->client?->email} for shipment {$shipment->shipping_reference}");
        } catch (\Exception $e) {
            logger()->error('Failed to send invoice: '.$e->getMessage());
        }
    }
}

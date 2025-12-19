<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use Filament\Forms;
use App\Models\City;
use App\Models\State;
use Filament\Actions;
use App\Models\Country;
use App\Models\Invoice;
use App\Models\Receiver;
use App\Models\ShipmentItem;
use App\Enums\ShippingStatus;
use App\Models\ShipmentUpdate;
use Filament\Facades\Filament;
use App\Service\InvoiceService;
use App\Service\NotificationService;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Wizard;
use Filament\Notifications\Notification;
use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProductResource;
use Illuminate\Support\Facades\Concurrency;
use App\Filament\Resources\ShipmentResource;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

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
                ->minValue(1),
            Forms\Components\TextInput::make('item_cost')
                ->required()
                ->label('Value ($)')
                ->default(0)
                ->numeric()
                ->prefix('$'),
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
            //->collapsible()
            ->itemLabel(fn (array $state): ?string => 
                isset($state['product_id']) ? 'Item #' : 'New Item'
            )
            ->schema(self::getItemSchema())
            ->columns($col); // Stack fields vertically on mobile

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
                                    ->createOptionForm(ClientResource::clientschema())
                                    ->editOptionForm(ClientResource::clientschema())
                                    ->preload()
                                    ->searchable()
                                    ->columnSpan(1),

                                Forms\Components\Hidden::make('tracking_number'),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Shipping Route')
                            ->schema([
                                Forms\Components\Select::make('origin_branch_id')
                                    ->label('From')
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
                        Forms\Components\Section::make('How many receivers?')
                            ->description('Select based on your shipment needs')
                            ->schema([
                                Forms\Components\Radio::make('receiver_mode')
                                    ->label('')
                                    ->options([
                                        'single' => 'Single Receiver - All items go to one person (fastest)',
                                        'multiple' => 'Multiple Receivers - Items go to different people',
                                    ])
                                    ->default('single')
                                    ->live()
                                    ->inline()
                                    ->columnSpanFull(),
                            ])
                            ->compact(),

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
                            ->visible(fn($get) => $get('receiver_mode') === 'single'),
                            //->collapsible(),

                        // Items Section - For Single Receiver (responsive)
                        Forms\Components\Section::make('Pickup Items')
                            ->description('Add all items - they will be assigned to the receiver above')
                            ->schema([
                                self::createItemsRepeater('pickupitems', 'pickupitems'),

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

                        // MULTIPLE RECEIVERS MODE
                        Forms\Components\Section::make('Receivers & Their Items')
                            ->description('Add receivers and assign items to each')
                            ->schema([
                                Forms\Components\Repeater::make('receivers')
                                    ->relationship('receivers')
                                    ->label('')
                                    ->addActionLabel('+ Add More Receiver')
                                    ->defaultItems(4)
                                    //->collapsible()
                                    ->cloneable()
                                    ->itemLabel(fn(array $state): ?string => $state['receiver_name'] ?? 'New Receiver')
                                    ->schema([
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
                                    //->collapsible()
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

                                        Forms\Components\Hidden::make('user_id')->default(fn() => FacadesAuth::user()->id),
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
                                    ->live(onBlur: true)
                                    ->numeric()
                                     ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $subtotal = $state ?? 0;
                                        $discount = $get('discount') ?? 0;
                                        $total = max($subtotal - $discount, 0);
                                        $set('total', $total);
                                    })
                                    ->default(0),

                                Forms\Components\TextInput::make('discount')
                                    ->label('Discount')
                                    ->prefix('$')
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $subtotal = $get('shipping_cost') ?? 0;
                                        $discount = $state ?? 0;
                                        $total = max($subtotal - $discount, 0);
                                        $set('total', $total);
                                    })
                                    ->default(0),

                                    
                                Forms\Components\TextInput::make('insurance')
                                    ->label('Insurance')
                                    ->prefix('$')
                                    ->dehydrated()
                                    ->default(0)
                                    ->extraAttributes(['class' => 'font-bold text-xl']),

                                Forms\Components\TextInput::make('total')
                                    ->label('Grand Total')
                                    ->prefix('$')
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

                        Forms\Components\Hidden::make('receiver_mode')->default('single'),
                    ]),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function generatePinAndSerial(): array
    {
        $pin = strtoupper(bin2hex(random_bytes(3)));
        $uniquePin = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $serialNumber = "KBZ" . $uniquePin;

        return [
            'pin' => $pin,
            'tracking_number' => $serialNumber,
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorderd_by'] = Auth::id();
        $data['tracking_number'] = $this->generatePinAndSerial()['tracking_number'];

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

        if ($receiverMode === 'single' && !empty($formData['single_receiver_name'])) {
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
        $shipment->save();

        $left = $amountopay - $paid;

        Invoice::create([
            'shipment_id' => $shipment->id,
            'total_amount' => $shipment->total,
            'status' => $left < 1 ? 'paid' : 'partial',
        ]);

        ShipmentUpdate::create([
            'shipment_id' => $shipment->id,
            'location' => $shipment->origin_branch_id ?? 'Origin',
            'status' => $shipment->status->value,
            'remarks' => 'Shipment created - ' . ucfirst($shipment->status->value),
        ]);

        $message = $this->buildNotificationMessage($shipment);

        $email = $shipment->client?->email;
        $phone = $shipment->client?->phone;

        if ($email || $phone) {
            Concurrency::defer([
                fn() => $phone ? NotificationService::sendSmsToSender($phone, $message) : null,
                fn() => $email ? $this->sendInvoiceToClient($shipment) : null,
            ]);
        }

        Notification::make()
            ->title('Shipment Created!')
            ->body("Tracking: {$shipment->tracking_number}")
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('download_pdf')
                    ->label('View Invoice')
                    ->url(route('shipping-invoice-pdf', $shipment->id))
                    ->openUrlInNewTab(),
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

        return "Dear {$clientName}, your shipment has been created. " .
            "Tracking: {$trackingNumber}. " .
            "Route: {$from} to {$to}. " .
            "Total: \${$total}. " .
            "Thank you for choosing Rose Door To Door Shipping!";
    }

    protected function sendInvoiceToClient($shipment): void
    {
        try {
            InvoiceService::sendInvoiceEmail($shipment);
            logger()->info("Invoice sent to {$shipment->client?->email} for shipment {$shipment->shipping_reference}");
        } catch (\Exception $e) {
            logger()->error("Failed to send invoice: " . $e->getMessage());
        }
    }
}
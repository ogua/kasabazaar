<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\City;
use Filament\Tables;
use App\Models\State;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Product;
use Nnjeim\World\World;
use App\Models\Shipment;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\ShippingStatus;
use App\Models\ShipmentUpdate;
use Filament\Facades\Filament;
use Livewire\Attributes\Layout;
use Filament\Resources\Resource;
use libphonenumber\NumberFormat;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use PragmaRX\Countries\Package\Countries;
use Filament\Tables\Enums\ActionsPosition;
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

    // protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('created_at', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Sender / Shipping Reference')
                ->description('Reference / Choose Sender')
                ->schema([
                    Forms\Components\TextInput::make('shipping_reference')->required(),

                    Forms\Components\Select::make('client_id')->label('Choose Sender')
                    ->required()->options(Client::get()->pluck('fullname_branch', 'id'))
                    ->preload()->searchable(),

                    // Forms\Components\TextInput::make('branch_id')
                    //     ->required()
                    //     ->maxLength(36),

                    Forms\Components\Hidden::make('tracking_number'),

                    Forms\Components\Select::make('origin_branch_id')
                        ->label('Shipping From')
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
                        ->preload()
                        ->searchable(),

                    Forms\Components\Select::make('destination_branch_id')
                        ->label('Shipping To')
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
                        ->preload()
                        ->searchable(),

                    Forms\Components\Hidden::make('status')->default('pending'),
                    //

                    //Forms\Components\DateTimePicker::make('shipped_at'),

                    Forms\Components\Hidden::make('shipping_cost')->default(0),

                    //Forms\Components\DatePicker::make('estimated_delivery_date'),

                    //Forms\Components\DateTimePicker::make('delivered_at'),
                ])
                ->columns(4),

            Forms\Components\Section::make('Receivers')
                ->description('Receivers Information')
                ->schema([
                    Forms\Components\Repeater::make('receivers')
                        ->relationship('receivers')
                        ->label('')
                        ->addActionLabel('Add More Receivers')
                        ->schema([
                            Forms\Components\TextInput::make('receiver_name')
                            ->required()->maxLength(255),

                            Forms\Components\Select::make('country')
                            ->label('Receiver Country')->required()
                            ->options(World::countries()->data->pluck('name', 'id'))->preload()->searchable()->live(),

                            Forms\Components\Select::make('state_region')
                                ->label('Receiver State/Region')
                               // ->required()
                                ->options(function ($get) {
                                    if (blank($get('country'))) {
                                        return [];
                                    }

                                    return State::where('country_id', $get('country'))->pluck('name', 'id');
                                })
                                ->searchable()
                                ->live(),

                            Forms\Components\Select::make('city')
                                ->label('Receiver city')
                                //->required()
                                ->options(function ($get) {
                                    if (blank($get('state_region'))) {
                                        return [];
                                    }

                                    return City::where('state_id', $get('state_region'))->pluck('name', 'id');
                                })
                                ->searchable(),

                            Forms\Components\Textarea::make('address')->label('Receiver address')->required()->columnSpanFull(),

                            PhoneInput::make('receiver_phone'),
                            //->required(),

                            Forms\Components\TextInput::make('receiver_email')
                                ->email()
                                //->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('receiver_id_type')
                                ->label('Receiver ID Type')
                                //->required()
                                ->options([
                                    'Drivers License' => 'Drivers License',
                                    'Ghana Card' => 'Ghana Card',
                                    'Voter ID Card' => 'Voter ID Card',
                                    'Passport' => 'Passport',
                                ])
                                ->searchable(),

                            Forms\Components\TextInput::make('receiver_id_number')
                            ->label('Receiver ID Number')
                           // ->required()
                            ->maxLength(255),

                            Forms\Components\Section::make('Shipping')
                            ->description('Shipping Items')
                            ->schema([
                                TableRepeater::make('items')
                                    ->label('')
                                    ->live()
                                    ->relationship()
                                    ->addActionLabel('Add More Items')
                                    ->colStyles([
                                        'box_no' => 'width: 200px;',
                                        'quantity' => 'width: 100px;',
                                        'item_cost' => 'width: 200px;',
                                    ])
                                    ->schema([Forms\Components\TextInput::make('box_no')->default(null), Forms\Components\Select::make('product_id')->label('Product')->required()->options(Product::pluck('name', 'id'))->preload()->searchable()->live(), Forms\Components\TextInput::make('quantity')->label('Qty')->required()->default(1)->numeric(), Forms\Components\TextInput::make('item_cost')->required()->label('Value')->numeric()->prefix('$')])
                                    ->columns(3),
                            ])
                            ->columnSpanFull(3),


                        ])
                        ->columns(4),
                ])
                ->columnSpanFull(),


            Forms\Components\Section::make('')
                ->description('')
                ->schema([
                    Forms\Components\Placeholder::make('totalitem')
                        ->content(function ($get, $set) {
                            $items = array_column($get('receivers'),'items');
                            $totalCount = array_sum(array_map('count', $items));

                            return 'Total item: '.$totalCount;
                        })
                        ->label(''),

                        Forms\Components\Placeholder::make('totqty')
                        ->content(function ($get, $set) {
                            $receivers = $get('receivers');
                    
                            // Initialize total quantity
                            $totqty = 0;
                    
                            // Iterate through each receiver and their items
                            foreach ($receivers as $receiver) {
                                if (isset($receiver['items']) && is_array($receiver['items'])) {
                                    $totqty += array_sum(array_column($receiver['items'], 'quantity'));
                                }
                            }
                    
                            return 'Total Quantities: ' . $totqty;
                        })
                        ->label(''),

                        Forms\Components\Placeholder::make('totqty')
                        ->content(function ($get, $set) {
                            $receivers = $get('receivers');
                    
                            // Initialize total quantity
                            $subtotal = 0;
                    
                            // Iterate through each receiver and their items
                            foreach ($receivers as $receiver) {
                                if (isset($receiver['items']) && is_array($receiver['items'])) {
                                    $subtotal += array_sum(array_column($receiver['items'], 'item_cost'));
                                }
                            }
                    
                            return "Subtotal: $" . number_format($subtotal, 2);
                        })
                        ->label(''),

                ])
                ->columns(3),

            Forms\Components\TextInput::make('shipping_cost')
            ->default(0)
            ->required()
            ->columnSpan(2)
            ->live(onBlur: true)
            ->prefix('$'),


            Forms\Components\Section::make('')
                ->description('')
                ->schema([
                    
                        Forms\Components\Placeholder::make('totqty')
                        ->content(function ($get, $set) {
                            $receivers = $get('receivers');
                    
                            // Initialize total quantity
                            $subtotal = 0;
                    
                            // Iterate through each receiver and their items
                            foreach ($receivers as $receiver) {
                                if (isset($receiver['items']) && is_array($receiver['items'])) {
                                    $subtotal += array_sum(array_column($receiver['items'], 'item_cost'));
                                }
                            }

                            $grandtotal = $subtotal  + $get('shipping_cost');

                            $set('total',$grandtotal);
                    
                            return "Grand Total: $" . number_format($grandtotal, 2);
                        })
                        ->columnSpanFull()
                        ->extraAttributes([
                            'class' => 'bg-primary-500 p-4 text-center font-bold text-white text-2xl'
                        ])
                        ->label(''),

                ])
                ->columnSpanFull(),

            Forms\Components\Hidden::make('total')->default(0),

            Forms\Components\Section::make('Payments')
                ->description('Shipping Cost & Payment Information')
                ->schema([
                    
                    Forms\Components\Repeater::make('Payments')
                        ->label('')
                        ->relationship('payments')
                        ->addActionLabel('Add Payment')
                        ->schema([
                            Forms\Components\Section::make('')
                                ->description('')
                                ->schema([
                                    Forms\Components\Hidden::make('branch_id')->default(Filament::getTenant()->id),

                                    Forms\Components\DateTimePicker::make('paid_on')->label('paid on')->default(0),

                                    Forms\Components\Select::make('paying_method')
                                        ->options([
                                            'CASH' => 'CASH',
                                            'PAYPAL' => 'PAYPAL',
                                            'CHEQUE' => 'CHEQUE',
                                            'CREDIT/DEBIT CARD' => 'CREDIT/DEBIT CARD',
                                            'BANK TRANSFER' => 'BANK TRANSFER',
                                            'Zelle' => 'Zelle',
                                            'Cash App' => 'Cash App',
                                            'WAIVED' => 'WAIVED',
                                        ])
                                        ->searchable()
                                        ->live()
                                        ->required(),

                                    // `cash_register_id`,
                                    // `account_id`,
                                    // `customer_id`,

                                    //payments

                                    Forms\Components\TextInput::make('bankname')->label('Bank name')->visible(fn($get): bool => $get('paying_method') == 'BANK TRANSFER')->required(),

                                    Forms\Components\TextInput::make('accountnumber')->label('Account number')->visible(fn($get): bool => $get('paying_method') == 'BANK TRANSFER')->required(),

                                    Forms\Components\TextInput::make('cheque_no')->label('Cheque number')->visible(fn($get): bool => $get('paying_method') == 'CHEQUE')->required(),

                                    Forms\Components\Hidden::make('user_id')->default(auth()->user()->id),

                                    Forms\Components\TextInput::make('amount')->label('Amount paid')->default(0)->prefix('$'),

                                    Forms\Components\Hidden::make('change')->default(0),

                                    Forms\Components\Hidden::make('payment_ref')->default('REF:' . date('Ymdhms')),

                                    Forms\Components\Textarea::make('payment_note')->columnSpanFull(),
                                ])
                                ->columns(3),
                        ])
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ])
                ->columns(4),

                Forms\Components\Section::make('Shipping / Payment Status')
                        ->description('')
                        ->visibleOn('create')
                        ->schema([
                            Forms\Components\Select::make('payment_status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'partial' => 'Partial',
                                ])
                                ->searchable()
                                ->required(),

                            Forms\Components\Select::make('status')
                                ->label('Shipping status')
                                ->options(ShippingStatus::class)
                                ->searchable()
                                ->required(),

                            Forms\Components\DateTimePicker::make('shipped_at'),
                            Forms\Components\DatePicker::make('estimated_delivery_date'),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Shipping / Payment Status')
                        ->description('')
                        ->visibleOn('edit')
                        ->schema([
                            Forms\Components\Select::make('payment_status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'partial' => 'Partial',
                                ])
                                ->searchable()
                                ->required(),

                            Forms\Components\DateTimePicker::make('shipped_at'),
                            Forms\Components\DatePicker::make('estimated_delivery_date'),
                        ])
                        ->columns(3),


        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shipping_reference')->searchable()->badge()->color('info'),

                Tables\Columns\TextColumn::make('client.name')->searchable()->badge(),

                Tables\Columns\TextColumn::make('payment_status')->label('Payment Status')->badge()->color('success'),

                Tables\Columns\TextColumn::make('status')->label('Shipping Status')->badge(),

                Tables\Columns\TextColumn::make('tracking_number')->searchable(),
                Tables\Columns\TextColumn::make('origin_branch_id')->label('Shipping From')->icon('heroicon-m-arrow-up')->iconColor('danger')->searchable(),

                Tables\Columns\TextColumn::make('destination_branch_id')->label('Shipping To')->icon('heroicon-m-arrow-down')->iconColor('warning')->searchable(),

                Tables\Columns\TextColumn::make('shipped_at')->dateTime()->sortable(),

                Tables\Columns\TextColumn::make('shipping_cost')->numeric()->sortable()->badge()->color('danger')->state(fn($record) => number_format($record->shipping_cost, 2))->prefix('$'),

                Tables\Columns\TextColumn::make('total')->sortable()->badge()->color('info')->state(fn($record) => number_format($record->total, 2))->prefix('$'),

                Tables\Columns\TextColumn::make('paid')->sortable()->badge()->color('warning')->state(fn($record) => number_format($record->paid, 2))->prefix('$'),

                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions(
                [
                    ActionGroup::make([
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make(),

                        Tables\Actions\Action::make('shipping')
                            ->label('Shipping Status')
                            ->icon('heroicon-m-truck')
                            ->color('info')
                            ->modalSubmitActionLabel('Update Shippign Status')
                            ->fillForm(function ($record) {
                                return [
                                    'status' => $record->status->value,
                                    'shipped_at' => $record->shipped_at,
                                    'estimated_delivery_date' => $record->estimated_delivery_date,
                                    'statusupdate' => $record->statusupdate,
                                ];
                            })
                            ->form([
                                Forms\Components\Section::make('')
                                    ->description('')
                                    ->schema([
                                        Forms\Components\TextInput::make('location')->required(),
                                        Forms\Components\Select::make('status')
                                            ->label('Shipping status')
                                            ->options(ShippingStatus::class)
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\DateTimePicker::make('shipped_at')->required(),
                                        Forms\Components\DatePicker::make('estimated_delivery_date')->required(),
                                        Forms\Components\Textarea::make('remarks')->columnSpanFull()->required(),

                                        TableRepeater::make('statusupdate')
                                            ->label('Status Updates Information')
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->schema([
                                                Forms\Components\TextInput::make('location')->readOnly(),

                                                Forms\Components\Select::make('status')
                                                    ->label('Shipping status')
                                                    ->options(ShippingStatus::class)
                                                    ->searchable()
                                                    ->disabled()
                                                    ->required(),

                                                Forms\Components\Textarea::make('remarks')->columnSpanFull()->readOnly()->required(),
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

                                $new = new ShipmentUpdate([
                                    'shipment_id' => $record->id,
                                    'location' => $data['location'],
                                    'status' => $data['status'],
                                    'remarks' => $data['remarks'],
                                ]);

                                $new->save();

                                //send sms to notify user

                                //send mail to notify user
                            }),

                        Tables\Actions\Action::make('receiver')
                            ->label('Receiver Information')
                            ->icon('heroicon-m-user')
                            ->fillForm(function ($record) {
                                return [
                                    'receivers' => $record->receivers,
                                ];
                            })
                            ->infolist([
                                RepeatableEntry::make('receivers')
                                    ->label('')
                                    ->schema([ImageEntry::make('receiver_name')->label(''), TextEntry::make('receiver_phone'), TextEntry::make('receiver_email'), TextEntry::make('country')->state(fn($record) => "{$record->mcountry?->name}, {$record->mstate?->name}, {$record->mcity?->name}"), TextEntry::make('receiver_phone'), TextEntry::make('address'), TextEntry::make('receiver_id_type'), TextEntry::make('receiver_id_number'), TextEntry::make('item_cost')])
                                    ->columns(4),
                            ])
                            ->modalSubmitAction(false)
                            ->modalHeading('Receiver Information'),

                        Tables\Actions\Action::make('items')
                            ->label('Shipping Items')
                            ->color('info')
                            ->icon('heroicon-m-truck')
                            ->modalWidth('5xl')
                            ->slideOver()
                            ->fillForm(function ($record) {
                                return [
                                    'items' => $record->items,
                                ];
                            })
                            ->infolist([
                                RepeatableEntry::make('items')
                                    ->label('') 
                                    ->schema([ImageEntry::make('product.product_image')->label('')->columnSpan(2), TextEntry::make('receiver.receiver_name')
                                    ->badge(), TextEntry::make('box_no'), TextEntry::make('product.name')->state(fn($record) => $record->product?->name . ' (' . $record->quantity . 'x)')->columnSpan(2), TextEntry::make('item_cost')->label('Value')])
                                    ->columns(7),
                            ])
                            ->modalSubmitAction(false),

                        Tables\Actions\Action::make('Print Invoice')->icon('heroicon-m-receipt-percent')->color('success')->url(fn($record) => route('shipping-invoice', $record->id), shouldOpenInNewTab: true),

                        Tables\Actions\Action::make('packingslip')->label('Packing Slip')->color('warning')->icon('heroicon-m-inbox-stack')->url(fn($record) => route('packing-slip', $record->id), shouldOpenInNewTab: true),

                        Tables\Actions\Action::make('payments')
                            ->label('View Payments')
                            ->color('info')
                            ->icon('heroicon-m-banknotes')
                            // ->modalWidth(MaxWidth::SixExtraLarge)
                            ->fillForm(function ($record) {
                                return [
                                    'payments' => $record->payments,
                                ];
                            })
                            ->infolist([
                                RepeatableEntry::make('payments')
                                    ->label('')
                                    ->schema([TextEntry::make('shipment.client.name')->label('Client'), TextEntry::make('payment_ref'), TextEntry::make('paying_method'), TextEntry::make('amount')->prefix('$'), TextEntry::make('paid_on'), TextEntry::make('enteredby.name')->label('Recorded By')])
                                    ->columns(6),
                            ])
                            ->modalHeading('Payment Information')
                            ->modalSubmitAction(false),

                        Tables\Actions\Action::make('updatepayments')->label('Add Payment')->icon('heroicon-m-banknotes')//->modalWidth(MaxWidth::ScreenMedium)
                        // ->modalSubmitAction(false)
                        ->modalCancelAction(false),
                    ]),
                ],
                position: ActionsPosition::BeforeColumns,
            )
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
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
            'index' => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
        ];
    }
}

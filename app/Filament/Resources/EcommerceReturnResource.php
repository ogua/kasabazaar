<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EcommerceReturnResource\Pages;
use App\Models\EcommerceReturn;
use App\Models\PickupStation;
use App\Services\ReturnService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Customer returns. Every return comes to one of our warehouses and is checked
 * before it goes back to the vendor (or the customer is refunded).
 */
class EcommerceReturnResource extends Resource
{
    protected static ?string $model = EcommerceReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 13;

    protected static ?string $navigationLabel = 'Returns';

    protected static bool $isScopedToTenant = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('return_number')->label('Return #')->searchable()->badge()->color('info')->copyable(),
                Tables\Columns\TextColumn::make('order.order_number')->label('Order')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('vendor.business_name')->label('Vendor'),
                Tables\Columns\TextColumn::make('reason')->limit(40)->tooltip(fn (EcommerceReturn $r) => $r->details ?: $r->reason),
                Tables\Columns\TextColumn::make('status')->badge()->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state)))
                    ->color(fn (string $state) => match ($state) {
                        'requested' => 'warning', 'approved', 'received' => 'info', 'inspected' => 'primary',
                        'returned_to_vendor', 'refunded' => 'success', default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Warehouse')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Requested')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'requested' => 'Requested', 'approved' => 'Approved', 'received' => 'Received at warehouse', 'inspected' => 'Inspected',
                    'returned_to_vendor' => 'Returned to vendor', 'refunded' => 'Refunded', 'rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn (EcommerceReturn $r) => $r->status === EcommerceReturn::REQUESTED)
                        ->modalDescription('Choose the warehouse the customer must bring or send the item to. They are notified with its address.')
                        ->form([
                            Forms\Components\Select::make('warehouse_id')->label('Return to warehouse')
                                ->options(fn () => PickupStation::warehouses()->orderBy('name')->pluck('name', 'id'))->required(),
                            Forms\Components\Textarea::make('notes')->rows(2),
                        ])
                        ->action(fn (EcommerceReturn $r, array $data) => app(ReturnService::class)->approve($r, PickupStation::findOrFail($data['warehouse_id']), $data['notes'] ?? null, auth()->user())),

                    Tables\Actions\Action::make('reject')
                        ->label('Decline')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn (EcommerceReturn $r) => $r->status === EcommerceReturn::REQUESTED)
                        ->form([Forms\Components\Textarea::make('reason')->required()->rows(2)])
                        ->action(fn (EcommerceReturn $r, array $data) => app(ReturnService::class)->reject($r, $data['reason'], auth()->user())),

                    Tables\Actions\Action::make('receive')
                        ->label('Received at warehouse')
                        ->icon('heroicon-m-inbox-arrow-down')
                        ->color('primary')
                        ->visible(fn (EcommerceReturn $r) => $r->status === EcommerceReturn::APPROVED)
                        ->requiresConfirmation()
                        ->action(fn (EcommerceReturn $r) => app(ReturnService::class)->receive($r, auth()->user())),

                    Tables\Actions\Action::make('inspect')
                        ->label('Inspect')
                        ->icon('heroicon-m-clipboard-document-check')
                        ->color('info')
                        ->visible(fn (EcommerceReturn $r) => $r->status === EcommerceReturn::RECEIVED)
                        ->form([
                            Forms\Components\Radio::make('result')->options([
                                'as_described' => 'Item is in order (as the customer says)',
                                'damaged' => 'Damaged',
                                'not_as_described' => 'Not as the customer described',
                            ])->required(),
                            Forms\Components\Textarea::make('notes')->rows(3),
                        ])
                        ->action(fn (EcommerceReturn $r, array $data) => app(ReturnService::class)->inspect($r, $data['result'], $data['notes'] ?? null, auth()->user())),

                    Tables\Actions\Action::make('return_to_vendor')
                        ->label('Return to vendor')
                        ->icon('heroicon-m-truck')
                        ->color('warning')
                        ->visible(fn (EcommerceReturn $r) => $r->status === EcommerceReturn::INSPECTED)
                        ->requiresConfirmation()
                        ->action(fn (EcommerceReturn $r) => app(ReturnService::class)->returnToVendor($r, auth()->user())),

                    Tables\Actions\Action::make('refund')
                        ->label('Refund customer')
                        ->icon('heroicon-m-banknotes')
                        ->color('success')
                        ->visible(fn (EcommerceReturn $r) => in_array($r->status, [EcommerceReturn::INSPECTED, EcommerceReturn::RETURNED_TO_VENDOR], true))
                        ->requiresConfirmation()
                        ->modalDescription('Marks the order refunded. Reverse the payment in your payment gateway dashboard.')
                        ->action(fn (EcommerceReturn $r) => app(ReturnService::class)->refund($r, auth()->user())),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEcommerceReturns::route('/'),
        ];
    }
}

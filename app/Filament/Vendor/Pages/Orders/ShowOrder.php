<?php

namespace App\Filament\Vendor\Pages\Orders;

use App\Services\Kasabazaar\VendorApi;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;

class ShowOrder extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.vendor.pages.orders.show-order';

    public array $order = [];

    public static function getRoutePath(Panel $panel): string
    {
        return '/orders/{order}';
    }

    public function mount(VendorApi $vendorApi, string $order): void
    {
        $this->order = $vendorApi->order($order);
    }

    public function processAction(): Action
    {
        return Action::make('process')
            ->label('Start Processing')
            ->visible(fn (): bool => $this->order['status'] === 'paid')
            ->action(function (VendorApi $vendorApi) {
                $vendorApi->processOrder($this->order['id']);
                $this->order = $vendorApi->order($this->order['id']);
            });
    }

    public function packAction(): Action
    {
        return Action::make('pack')
            ->label('Mark Packed')
            ->visible(fn (): bool => $this->order['status'] === 'processing')
            ->action(function (VendorApi $vendorApi) {
                $vendorApi->packOrder($this->order['id']);
                $this->order = $vendorApi->order($this->order['id']);
            });
    }

    public function dispatchOrderAction(): Action
    {
        return Action::make('dispatchOrder')
            ->label('Send to KASAROSE warehouse')
            ->visible(fn (): bool => $this->order['status'] === 'packed')
            ->modalDescription('Vendors do not deliver to buyers. Send the packed order to a KASAROSE warehouse, where we inspect it before it goes on to the customer.')
            ->schema([
                Select::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(fn (): array => collect(app(VendorApi::class)->warehouses())->mapWithKeys(fn (array $w): array => [$w['id'] => $w['name'].' — '.$w['address']])->all())
                    ->required(),
                TextInput::make('note')->label('Courier / tracking note')->maxLength(255),
            ])
            ->action(function (array $data, VendorApi $vendorApi) {
                $vendorApi->dispatchOrder($this->order['id'], $data['warehouse_id'], $data['note'] ?? null);
                $this->order = $vendorApi->order($this->order['id']);
                Notification::make()->title('Order sent to the warehouse.')->success()->send();
            });
    }

    public function getTitle(): string
    {
        return 'Order '.($this->order['order_number'] ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->processAction(),
            $this->packAction(),
            $this->dispatchOrderAction(),
        ];
    }
}

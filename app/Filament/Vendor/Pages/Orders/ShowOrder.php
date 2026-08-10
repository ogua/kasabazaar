<?php

namespace App\Filament\Vendor\Pages\Orders;

use App\Services\Kasabazaar\KasabazaarClient;
use App\Services\Kasabazaar\VendorApi;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;

class ShowOrder extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.vendor.pages.orders.show-order';

    public array $order = [];

    public string $courier = '';

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

    public function createShipmentAction(): Action
    {
        return Action::make('createShipment')
            ->label('Create Shipment')
            ->visible(fn (): bool => $this->order['status'] === 'packed' && empty($this->order['shipment']))
            ->schema([
                TextInput::make('courier')->label('Courier Name')->required(),
            ])
            ->action(function (array $data, KasabazaarClient $client, VendorApi $vendorApi) {
                $client->post("marketplace/vendor/orders/{$this->order['id']}/shipment", ['courier' => $data['courier']]);
                $this->order = $vendorApi->order($this->order['id']);
            });
    }

    public function dispatchOrderAction(): Action
    {
        return Action::make('dispatchOrder')
            ->label('Mark Dispatched')
            ->visible(fn (): bool => $this->order['status'] === 'packed' && ! empty($this->order['shipment']))
            ->action(function (VendorApi $vendorApi) {
                $vendorApi->dispatchOrder($this->order['id']);
                $this->order = $vendorApi->order($this->order['id']);
                Notification::make()->title('Order dispatched.')->success()->send();
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
            $this->createShipmentAction(),
            $this->dispatchOrderAction(),
        ];
    }
}

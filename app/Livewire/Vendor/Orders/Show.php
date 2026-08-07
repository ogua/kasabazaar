<?php

namespace App\Livewire\Vendor\Orders;

use App\Services\Kasabazaar\KasabazaarClient;
use App\Services\Kasabazaar\VendorApi;
use Livewire\Component;

class Show extends Component
{
    public array $order = [];

    public string $courier = '';

    public function mount(string $order, VendorApi $vendorApi): void
    {
        $this->order = $vendorApi->order($order);
    }

    public function process(VendorApi $vendorApi): void
    {
        $vendorApi->processOrder($this->order['id']);
        $this->order = $vendorApi->order($this->order['id']);
    }

    public function pack(VendorApi $vendorApi): void
    {
        $vendorApi->packOrder($this->order['id']);
        $this->order = $vendorApi->order($this->order['id']);
    }

    public function createShipment(KasabazaarClient $client, VendorApi $vendorApi): void
    {
        $client->post("marketplace/vendor/orders/{$this->order['id']}/shipment", ['courier' => $this->courier]);
        $this->order = $vendorApi->order($this->order['id']);
    }

    public function dispatchOrder(VendorApi $vendorApi): void
    {
        $vendorApi->dispatchOrder($this->order['id']);
        $this->order = $vendorApi->order($this->order['id']);
    }

    public function render()
    {
        return view('livewire.vendor.orders.show')->layout('vendor.layouts.dashboard', [
            'title' => 'Order '.($this->order['order_number'] ?? ''),
        ]);
    }
}

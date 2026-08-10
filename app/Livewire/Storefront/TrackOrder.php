<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\OrdersApi;
use Livewire\Component;

class TrackOrder extends Component
{
    public string $orderNumber = '';

    public string $contact = '';

    public array $order = [];

    public bool $searched = false;

    public ?string $error = null;

    public function mount(): void
    {
        $this->orderNumber = (string) request()->query('order_number', '');
    }

    public function track(OrdersApi $ordersApi): void
    {
        $this->validate([
            'orderNumber' => 'required|string',
            'contact' => 'required|string',
        ], [], ['orderNumber' => 'order number', 'contact' => 'phone or email']);

        $this->searched = true;
        $this->error = null;
        $this->order = [];

        try {
            $this->order = $ordersApi->trackPublic($this->orderNumber, $this->contact);
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.storefront.track-order')->layout('storefront.layouts.app', [
            'title' => 'Track Your Order',
        ]);
    }
}

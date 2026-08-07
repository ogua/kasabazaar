<?php

namespace App\Livewire\Vendor\Orders;

use App\Services\Kasabazaar\VendorApi;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $status = '';

    public function render(VendorApi $vendorApi)
    {
        $response = $vendorApi->orders(array_filter([
            'status' => $this->status,
            'page' => $this->getPage(),
            'per_page' => 20,
        ]));

        return view('livewire.vendor.orders.index', [
            'orders' => $response->data,
            'meta' => $response->meta,
        ])->layout('vendor.layouts.dashboard', ['title' => 'Orders']);
    }
}

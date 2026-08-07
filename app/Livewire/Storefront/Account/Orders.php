<?php

namespace App\Livewire\Storefront\Account;

use App\Services\Kasabazaar\OrdersApi;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithPagination;

    public string $status = '';

    public function render(OrdersApi $ordersApi)
    {
        $response = $ordersApi->list(array_filter([
            'status' => $this->status,
            'page' => $this->getPage(),
            'per_page' => 15,
        ]));

        return view('livewire.storefront.account.orders', [
            'orders' => $response->data,
            'meta' => $response->meta,
        ])->layout('storefront.layouts.app', ['title' => 'My Orders']);
    }
}

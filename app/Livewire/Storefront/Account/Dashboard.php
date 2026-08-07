<?php

namespace App\Livewire\Storefront\Account;

use App\Services\Kasabazaar\OrdersApi;
use Livewire\Component;

class Dashboard extends Component
{
    public array $recentOrders = [];

    public function mount(OrdersApi $ordersApi): void
    {
        $this->recentOrders = $ordersApi->list(['per_page' => 5])->data;
    }

    public function render()
    {
        return view('livewire.storefront.account.dashboard')->layout('storefront.layouts.app', ['title' => 'My Account']);
    }
}

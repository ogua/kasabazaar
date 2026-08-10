<?php

namespace App\Livewire\Storefront\Account;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\OrdersApi;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Dashboard extends Component
{
    public array $recentOrders = [];

    public ?string $error = null;

    public function mount(OrdersApi $ordersApi): void
    {
        try {
            $this->recentOrders = $ordersApi->list(['per_page' => 5])->data;
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.account.dashboard: failed to load recent orders', ['message' => $e->getMessage()]);
            $this->error = 'We\'re having trouble loading your recent orders right now.';
        }
    }

    public function render()
    {
        return view('livewire.storefront.account.dashboard')->layout('storefront.layouts.app', ['title' => 'My Account']);
    }
}

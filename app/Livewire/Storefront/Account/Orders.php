<?php

namespace App\Livewire\Storefront\Account;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\OrdersApi;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render(OrdersApi $ordersApi)
    {
        $orders = [];
        $meta = [];
        $error = null;

        try {
            $response = $ordersApi->list(array_filter([
                'status' => $this->status,
                'page' => $this->getPage(),
                'per_page' => 15,
            ]));

            $orders = $response->data;
            $meta = $response->meta;
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.account.orders: failed to load orders', ['message' => $e->getMessage()]);
            $error = 'We\'re having trouble loading your orders right now. Please try again shortly.';
        }

        return view('livewire.storefront.account.orders', [
            'orders' => $orders,
            'meta' => $meta,
            'error' => $error,
        ])->layout('storefront.layouts.app', ['title' => 'My Orders']);
    }
}

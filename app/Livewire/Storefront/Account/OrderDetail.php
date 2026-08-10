<?php

namespace App\Livewire\Storefront\Account;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\OrdersApi;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class OrderDetail extends Component
{
    public array $orderData = [];

    public array $tracking = [];

    public int $ratingValue = 5;

    public string $ratingComment = '';

    public bool $rated = false;

    public bool $notFound = false;

    public ?string $error = null;

    public function mount(string $order, OrdersApi $ordersApi): void
    {
        try {
            $this->orderData = $ordersApi->show($order);
            $this->tracking = $ordersApi->tracking($order);
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.account.order: failed to load order', ['order' => $order, 'message' => $e->getMessage()]);
            $this->notFound = true;
        }
    }

    public function cancel(OrdersApi $ordersApi): void
    {
        try {
            $ordersApi->cancel($this->orderData['id']);
            $this->orderData = $ordersApi->show($this->orderData['id']);
            $this->dispatch('toast', type: 'success', message: 'Order cancelled.');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function submitRating(OrdersApi $ordersApi): void
    {
        try {
            $ordersApi->rate($this->orderData['id'], $this->ratingValue, $this->ratingComment);
            $this->rated = true;
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.storefront.account.order-detail', [
            'order' => $this->orderData,
        ])->layout('storefront.layouts.app', [
            'title' => $this->notFound ? 'Order Not Found' : 'Order '.($this->orderData['order_number'] ?? ''),
        ]);
    }
}

<?php

namespace App\Livewire\Storefront\Account;

use App\Livewire\Storefront\Concerns\StartsPayment;
use App\Services\Kasabazaar\CheckoutApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\OrdersApi;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class OrderDetail extends Component
{
    use StartsPayment;

    public array $orderData = [];

    public int $ratingValue = 5;

    public string $ratingComment = '';

    public bool $rated = false;

    public bool $notFound = false;

    public ?string $error = null;

    public function mount(string $order, OrdersApi $ordersApi): void
    {
        try {
            $this->orderData = $ordersApi->show($order);
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

    /**
     * Restarts payment for an order whose checkout went through but whose
     * payment never completed (gateway closed, card declined, network drop).
     * Payment covers the whole order group — every vendor order placed in
     * the same checkout — exactly as at checkout.
     */
    public function payNow(CheckoutApi $checkoutApi): void
    {
        if (! $this->awaitingPayment() || empty($this->orderData['order_group_id'])) {
            return;
        }

        try {
            $failure = $this->startPayment($checkoutApi, $this->orderData['order_group_id']);
        } catch (KasabazaarApiException $e) {
            $failure = $e->getMessage();
        }

        if ($failure) {
            $this->dispatch('toast', type: 'error', message: $failure);
        }
    }

    public function awaitingPayment(): bool
    {
        return ($this->orderData['status'] ?? null) === 'awaiting_payment'
            && ($this->orderData['payment_status'] ?? null) === 'pending';
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

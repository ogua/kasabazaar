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

    public bool $showReturnForm = false;

    public string $returnReason = '';

    public string $returnDetails = '';

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

    /**
     * Pays the delivery cost the shipping team set. The customer bears it and pays it
     * separately from the items.
     */
    public function payDeliveryFee(OrdersApi $ordersApi): void
    {
        if (($this->orderData['delivery_fee_status'] ?? null) !== 'quoted') {
            return;
        }

        try {
            $checkout = $ordersApi->payDeliveryFee($this->orderData['id']);
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->redirect($checkout['authorization_url'], navigate: false);
    }

    /**
     * Whether the customer can still ask to return this order: delivered, inside the published
     * return window, and no return already open. The backend enforces the same rules.
     */
    public function canRequestReturn(): bool
    {
        if (($this->orderData['status'] ?? null) !== 'delivered') {
            return false;
        }

        $open = collect($this->orderData['returns'] ?? [])->contains(fn (array $r) => in_array($r['status'], ['requested', 'approved', 'received', 'inspected'], true));

        return ! $open;
    }

    public function requestReturn(OrdersApi $ordersApi): void
    {
        $this->validate([
            'returnReason' => 'required|string|max:500',
            'returnDetails' => 'nullable|string|max:2000',
        ]);

        try {
            $ordersApi->requestReturn($this->orderData['id'], $this->returnReason, $this->returnDetails ?: null);
            $this->orderData = $ordersApi->show($this->orderData['id']);
            $this->showReturnForm = false;
            $this->reset('returnReason', 'returnDetails');
            $this->dispatch('toast', type: 'success', message: 'Return requested. We will tell you where to bring the item.');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
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

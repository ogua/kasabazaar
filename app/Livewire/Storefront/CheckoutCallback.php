<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\CheckoutApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\OrdersApi;
use Livewire\Attributes\On;
use Livewire\Component;

class CheckoutCallback extends Component
{
    public string $status = 'verifying'; // verifying|success|failed

    public string $message = '';

    public ?string $orderGroupId = null;

    public ?string $stripeClientSecret = null;

    public array $placedOrders = [];

    public function mount(CheckoutApi $checkoutApi, OrdersApi $ordersApi): void
    {
        $reference = request()->query('reference') ?? request()->query('trxref');
        $gateway = request()->query('gateway');

        if ($reference) {
            $this->verifyPaystack($checkoutApi, $ordersApi, $reference);

            return;
        }

        if ($gateway === 'stripe') {
            $this->stripeClientSecret = request()->query('client_secret');
            $this->orderGroupId = request()->query('order_group_id');
            $this->status = $this->stripeClientSecret ? 'awaiting-stripe' : 'failed';

            return;
        }

        $this->status = 'failed';
        $this->message = 'No payment reference found.';
    }

    #[On('stripe-confirmed')]
    public function confirmStripePayment(CheckoutApi $checkoutApi, OrdersApi $ordersApi, string $paymentIntentId): void
    {
        try {
            $result = $checkoutApi->verifyStripe($paymentIntentId);
            $this->status = 'success';
            $this->orderGroupId = $result['order_group_id'] ?? $this->orderGroupId;
            $this->loadPlacedOrders($ordersApi);
            $this->dispatch('cart-updated');
        } catch (KasabazaarApiException $e) {
            $this->status = 'failed';
            $this->message = $e->getMessage();
        }
    }

    private function verifyPaystack(CheckoutApi $checkoutApi, OrdersApi $ordersApi, string $reference): void
    {
        try {
            $result = $checkoutApi->verifyPaystack($reference);
            $this->status = 'success';
            $this->orderGroupId = $result['order_group_id'] ?? null;
            $this->loadPlacedOrders($ordersApi);
            $this->dispatch('cart-updated');
        } catch (KasabazaarApiException $e) {
            $this->status = 'failed';
            $this->message = $e->getMessage();
        }
    }

    private function loadPlacedOrders(OrdersApi $ordersApi): void
    {
        if (! $this->orderGroupId) {
            return;
        }

        try {
            $this->placedOrders = $ordersApi->list(['order_group_id' => $this->orderGroupId])->data ?? [];
        } catch (KasabazaarApiException) {
            $this->placedOrders = [];
        }
    }

    public function render()
    {
        return view('livewire.storefront.checkout-callback')->layout('storefront.layouts.app', ['title' => 'Payment']);
    }
}

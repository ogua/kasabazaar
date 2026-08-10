<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\CartApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class CartPage extends Component
{
    public array $cart = [];

    public string $couponCode = '';

    public string $couponError = '';

    public ?string $error = null;

    public function mount(CartApi $cartApi): void
    {
        $this->load($cartApi);
    }

    #[On('cart-updated')]
    public function load(CartApi $cartApi): void
    {
        try {
            $this->cart = $cartApi->show();
            $this->error = null;
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.cart: failed to load cart', ['message' => $e->getMessage()]);
            $this->error = 'We\'re having trouble loading your cart right now. Please refresh or try again shortly.';
        }
    }

    public function updateQuantity(CartApi $cartApi, string $itemId, int $quantity): void
    {
        try {
            $this->cart = $cartApi->updateItem($itemId, $quantity);
            $this->dispatch('cart-updated');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function removeItem(CartApi $cartApi, string $itemId): void
    {
        try {
            $cartApi->removeItem($itemId);
            $this->load($cartApi);
            $this->dispatch('cart-updated');
            $this->dispatch('toast', type: 'success', message: 'Item removed from cart.');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function applyCoupon(CartApi $cartApi): void
    {
        try {
            $this->cart = $cartApi->applyCoupon($this->couponCode);
            $this->couponError = '';
        } catch (KasabazaarApiException $e) {
            $this->couponError = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.storefront.cart-page')->layout('storefront.layouts.app', ['title' => 'Shopping Cart']);
    }
}

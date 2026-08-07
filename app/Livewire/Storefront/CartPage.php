<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\CartApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Livewire\Attributes\On;
use Livewire\Component;

class CartPage extends Component
{
    public array $cart = [];

    public string $couponCode = '';

    public string $couponError = '';

    public function mount(CartApi $cartApi): void
    {
        $this->load($cartApi);
    }

    #[On('cart-updated')]
    public function load(CartApi $cartApi): void
    {
        $this->cart = $cartApi->show();
    }

    public function updateQuantity(CartApi $cartApi, string $itemId, int $quantity): void
    {
        $this->cart = $cartApi->updateItem($itemId, $quantity);
        $this->dispatch('cart-updated');
    }

    public function removeItem(CartApi $cartApi, string $itemId): void
    {
        $cartApi->removeItem($itemId);
        $this->load($cartApi);
        $this->dispatch('cart-updated');
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

<?php

namespace App\Livewire\Storefront\Header;

use App\Services\Kasabazaar\CartApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Livewire\Attributes\On;
use Livewire\Component;

class CartIcon extends Component
{
    public int $count = 0;

    public float $subtotalGhs = 0;

    public function mount(CartApi $cartApi): void
    {
        $this->refresh($cartApi);
    }

    #[On('cart-updated')]
    public function refresh(CartApi $cartApi): void
    {
        try {
            $cart = $cartApi->show();
            $this->count = collect($cart['items'] ?? [])->sum('quantity');
            $this->subtotalGhs = (float) ($cart['subtotal_ghs'] ?? 0);
        } catch (KasabazaarApiException) {
            $this->count = 0;
            $this->subtotalGhs = 0;
        }
    }

    public function render()
    {
        return view('livewire.storefront.header.cart-icon');
    }
}

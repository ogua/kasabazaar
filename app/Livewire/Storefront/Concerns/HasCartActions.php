<?php

namespace App\Livewire\Storefront\Concerns;

use App\Services\Kasabazaar\CartApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\WishlistApi;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

trait HasCartActions
{
    #[On('add-to-cart')]
    public function addToCart(string $productId, int $quantity = 1): void
    {
        try {
            app(CartApi::class)->addItem($productId, $quantity);
            $this->dispatch('cart-updated');
            $this->dispatch('toast', type: 'success', message: 'Added to cart.');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    #[On('toggle-wishlist')]
    public function toggleWishlist(string $productId): void
    {
        if (! Auth::check()) {
            $this->dispatch('toast', type: 'error', message: 'Please sign in to use your wishlist.');

            return;
        }

        try {
            app(WishlistApi::class)->toggle($productId);
            $this->dispatch('toast', type: 'success', message: 'Wishlist updated.');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }
}

<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\HasCartActions;
use App\Services\Kasabazaar\WishlistApi;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class WishlistPage extends Component
{
    use HasCartActions;

    public array $products = [];

    public function mount(WishlistApi $wishlistApi): void
    {
        $this->products = Auth::check() ? $wishlistApi->index() : [];
    }

    #[On('toggle-wishlist')]
    public function toggleWishlist(string $productId): void
    {
        if (! Auth::check()) {
            $this->dispatch('toast', type: 'error', message: 'Please sign in to use your wishlist.');

            return;
        }

        app(WishlistApi::class)->remove($productId);
        $this->products = array_values(array_filter($this->products, fn ($p) => $p['id'] !== $productId));
        $this->dispatch('toast', type: 'success', message: 'Removed from wishlist.');
    }

    public function render()
    {
        return view('livewire.storefront.wishlist-page')->layout('storefront.layouts.app', ['title' => 'My Wishlist']);
    }
}

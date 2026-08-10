<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\HasCartActions;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\WishlistApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class WishlistPage extends Component
{
    use HasCartActions;

    public array $products = [];

    public ?string $error = null;

    public function mount(WishlistApi $wishlistApi): void
    {
        if (! Auth::check()) {
            return;
        }

        try {
            $this->products = $wishlistApi->index();
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.wishlist: failed to load wishlist', ['message' => $e->getMessage()]);
            $this->error = 'We\'re having trouble loading your wishlist right now. Please refresh or try again shortly.';
        }
    }

    // Overrides HasCartActions::toggleWishlist() — on this page every product
    // shown is already in the wishlist, so the action always removes rather
    // than toggling.
    #[On('toggle-wishlist')]
    public function toggleWishlist(string $productId): void
    {
        if (! Auth::check()) {
            $this->dispatch('toast', type: 'error', message: 'Please sign in to use your wishlist.');

            return;
        }

        try {
            app(WishlistApi::class)->remove($productId);
            $this->products = array_values(array_filter($this->products, fn ($p) => $p['id'] !== $productId));
            $this->dispatch('toast', type: 'success', message: 'Removed from wishlist.');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.storefront.wishlist-page')->layout('storefront.layouts.app', ['title' => 'My Wishlist']);
    }
}

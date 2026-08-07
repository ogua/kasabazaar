<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\HasCartActions;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProductDetail extends Component
{
    use HasCartActions;

    public string $productId;

    public array $product = [];

    public array $reviews = [];

    public int $quantity = 1;

    public function mount(string $product, ProductsApi $productsApi): void
    {
        $this->productId = $product;
        $this->product = $productsApi->show($product);
        $this->reviews = $productsApi->reviews($product)->data ?? [];
    }

    public function increment(): void
    {
        $this->quantity++;
    }

    public function decrement(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function addThisToCart(): void
    {
        $this->addToCart($this->productId, $this->quantity);
    }

    public function submitReview(ProductsApi $productsApi, string $rating, ?string $title, ?string $body): void
    {
        if (! Auth::check()) {
            $this->dispatch('toast', type: 'error', message: 'Please sign in to leave a review.');

            return;
        }

        try {
            $productsApi->submitReview($this->productId, [
                'rating' => (int) $rating,
                'title' => $title,
                'body' => $body,
            ]);
            $this->reviews = $productsApi->reviews($this->productId)->data ?? [];
            $this->dispatch('toast', type: 'success', message: 'Thanks for your review!');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.storefront.product-detail')->layout('storefront.layouts.app', [
            'title' => $this->product['name'] ?? 'Product',
        ]);
    }
}

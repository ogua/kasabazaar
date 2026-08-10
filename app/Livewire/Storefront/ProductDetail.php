<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\HasCartActions;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ProductDetail extends Component
{
    use HasCartActions;

    public string $productId;

    public array $productData = [];

    public array $reviews = [];

    public int $quantity = 1;

    public bool $notFound = false;

    public function mount(string $product, ProductsApi $productsApi): void
    {
        $this->productId = $product;

        try {
            $this->productData = $productsApi->show($product);
            $this->reviews = $productsApi->reviews($product)->data ?? [];
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.product: failed to load product', ['product' => $product, 'message' => $e->getMessage()]);
            $this->notFound = true;
        }
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
        return view('livewire.storefront.product-detail', [
            'product' => $this->productData,
        ])->layout('storefront.layouts.app', [
            'title' => $this->productData['name'] ?? ($this->notFound ? 'Product Not Found' : 'Product'),
        ]);
    }
}

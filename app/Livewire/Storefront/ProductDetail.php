<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\HasCartActions;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class ProductDetail extends Component
{
    use HasCartActions;

    public string $productId;

    public array $productData = [];

    public array $reviews = [];

    /** @var array{similar: array<int, array>, from_vendor: array<int, array>} */
    public array $related = ['similar' => [], 'from_vendor' => []];

    public int $quantity = 1;

    public bool $unavailable = false;

    public function mount(string $product, ProductsApi $productsApi): void
    {
        $this->productId = $product;

        try {
            $this->productData = $productsApi->show($product);
            $this->reviews = $productsApi->reviews($product)->data ?? [];
        } catch (KasabazaarApiException $e) {
            // A missing or delisted product is a real 404, so search engines drop
            // it; anything else is an outage, shown as a retryable error.
            if ($e->status === 404) {
                abort(404);
            }

            Log::warning('storefront.product: failed to load product', ['product' => $product, 'message' => $e->getMessage()]);
            $this->unavailable = true;

            return;
        }

        // Related items are a bonus: if the call fails the page still renders.
        try {
            $this->related = Cache::remember("storefront.product.{$product}.related", 300, fn () => $productsApi->related($product)) + $this->related;
        } catch (KasabazaarApiException $e) {
            Log::info('storefront.product: related products unavailable', ['product' => $product, 'message' => $e->getMessage()]);
        }
    }

    public function increment(): void
    {
        // Stock is only enforced when the API sends it; the backend re-checks on add.
        $stock = isset($this->productData['stock']) ? (int) $this->productData['stock'] : null;

        $this->quantity = $stock === null ? $this->quantity + 1 : min($this->quantity + 1, max(1, $stock));
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
        $product = $this->productData;
        $description = trim(preg_replace('/\s+/', ' ', strip_tags($product['description'] ?? '')));
        $price = $product['discount_price_ghs'] ?? $product['price_ghs'] ?? null;

        return view('livewire.storefront.product-detail', [
            'product' => $product,
        ])->layout('storefront.layouts.app', [
            'title' => $product['name'] ?? 'Product unavailable',
            // Shared product links (WhatsApp especially) preview this card.
            'metaDescription' => $product
                ? Str::limit(($price !== null ? 'GHS '.number_format((float) $price, 2).' · ' : '').($description ?: 'Buy '.$product['name'].' on '.config('app.name').'.'), 160)
                : null,
            'ogImage' => $product['images'][0]['url'] ?? null,
            'ogType' => $product ? 'product' : 'website',
            'noindex' => $this->unavailable,
        ]);
    }
}

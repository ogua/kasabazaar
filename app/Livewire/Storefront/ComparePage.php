<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\HasCartActions;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\ProductsApi;
use App\Support\CompareList;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Side-by-side comparison of up to four products: price, rating, seller,
 * stock and the union of every product's specifications. Lowest price and
 * best rating are highlighted.
 */
class ComparePage extends Component
{
    use HasCartActions;

    public function remove(CompareList $compare, string $productId): void
    {
        $compare->remove($productId);
        $this->dispatch('compare-updated', ids: $compare->ids());
    }

    #[On('compare-updated')]
    public function refreshList(): void
    {
        // Re-render with the latest session list.
    }

    public function render(CompareList $compare, ProductsApi $productsApi)
    {
        $products = [];

        foreach ($compare->ids() as $id) {
            try {
                $products[] = Cache::remember("storefront.product.{$id}", 60, fn () => $productsApi->show($id));
            } catch (KasabazaarApiException $e) {
                // Delisted since it was added — drop it quietly.
                Log::info('storefront.compare: product unavailable', ['product' => $id, 'message' => $e->getMessage()]);
                $compare->remove($id);
            }
        }

        $prices = collect($products)->map(fn (array $p) => (float) ($p['discount_price_ghs'] ?? $p['price_ghs'] ?? 0));
        $ratings = collect($products)->map(fn (array $p) => ($p['reviews_count'] ?? 0) > 0 ? (float) $p['average_rating'] : null);

        $specKeys = collect($products)
            ->flatMap(fn (array $p) => array_keys((array) ($p['specifications'] ?? [])))
            ->unique()
            ->values()
            ->all();

        return view('livewire.storefront.compare-page', [
            'products' => $products,
            'lowestPrice' => count($products) > 1 ? $prices->min() : null,
            'bestRating' => $ratings->filter()->count() > 1 ? $ratings->filter()->max() : null,
            'specKeys' => $specKeys,
            'specLabel' => fn (string $key) => Str::of($key)->replace(['_', '-'], ' ')->title()->toString(),
            'max' => CompareList::MAX_ITEMS,
        ])->layout('storefront.layouts.app')->title('Compare products');
    }
}

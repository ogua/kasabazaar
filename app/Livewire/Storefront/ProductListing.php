<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\HasCartActions;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductListing extends Component
{
    use HasCartActions, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category_id = '';

    #[Url]
    public ?float $price_min = null;

    #[Url]
    public ?float $price_max = null;

    #[Url]
    public bool $in_stock = false;

    #[Url]
    public string $sort = 'newest';

    public function mount(?string $category = null): void
    {
        if ($category) {
            $this->category_id = $category;
        }
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render(ProductsApi $productsApi)
    {
        $products = [];
        $meta = [];
        $error = null;

        try {
            $response = $productsApi->list(array_filter([
                'search' => $this->search,
                'category_id' => $this->category_id,
                'price_min' => $this->price_min,
                'price_max' => $this->price_max,
                'in_stock' => $this->in_stock ? 1 : null,
                'sort' => $this->sort,
                'page' => $this->getPage(),
                'per_page' => 20,
            ]));

            $products = $response->data;
            $meta = $response->meta;
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.shop: failed to load products', ['message' => $e->getMessage()]);
            $error = 'We\'re having trouble loading products right now. Please try again shortly.';
        }

        return view('livewire.storefront.product-listing', [
            'products' => $products,
            'meta' => $meta,
            'error' => $error,
        ])->layout('storefront.layouts.app');
    }
}

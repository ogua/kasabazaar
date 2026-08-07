<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\HasCartActions;
use App\Models\Banner;
use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Home extends Component
{
    use HasCartActions;

    public array $categories = [];

    public array $featured = [];

    public array $newArrivals = [];

    public array $bestSellers = [];

    public $banners;

    public function mount(ProductsApi $productsApi): void
    {
        $home = Cache::remember('storefront.home', 60, fn () => $productsApi->home());

        $this->categories = $home['categories'] ?? [];
        $this->featured = $home['featured'] ?? [];
        $this->newArrivals = $home['new_arrivals'] ?? [];
        $this->bestSellers = $home['best_sellers'] ?? [];
        $this->banners = Banner::active()->homepageHero()->orderBy('sort_order')->get();
    }

    public function render()
    {
        return view('livewire.storefront.home')->layout('storefront.layouts.app');
    }
}

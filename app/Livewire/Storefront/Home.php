<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\HasCartActions;
use App\Services\Kasabazaar\AuctionsApi;
use App\Services\Kasabazaar\BannersApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Home extends Component
{
    use HasCartActions;

    public array $categories = [];

    public array $featured = [];

    public array $newArrivals = [];

    public array $bestSellers = [];

    public array $dealToday = [];

    public array $banners = [];

    public array $liveAuctions = [];

    public ?string $error = null;

    public function mount(ProductsApi $productsApi, BannersApi $bannersApi, AuctionsApi $auctionsApi): void
    {
        try {
            $home = Cache::remember('storefront.home', 60, fn () => $productsApi->home());

            $this->categories = $home['categories'] ?? [];
            $this->featured = $home['featured'] ?? [];
            $this->newArrivals = $home['new_arrivals'] ?? [];
            $this->bestSellers = $home['best_sellers'] ?? [];
            $this->dealToday = $home['deal_today'] ?? [];
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.home: failed to load marketplace data', ['message' => $e->getMessage()]);
            $this->error = 'We\'re having trouble loading products right now. Please refresh or check back shortly.';
        }

        // Banners and the auction strip are decoration: if either call fails the
        // page still renders (brand hero / no strip) rather than erroring.
        try {
            $this->banners = Cache::remember('storefront.home.banners', 60, fn () => $bannersApi->forPosition('homepage_hero'));
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.home: failed to load banners', ['message' => $e->getMessage()]);
        }

        try {
            $this->liveAuctions = Cache::remember('storefront.home.auctions', 30, fn () => $auctionsApi->list(['state' => 'current', 'per_page' => 5])->data);
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.home: failed to load auctions', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.storefront.home')->layout('storefront.layouts.app');
    }
}

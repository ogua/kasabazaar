<?php

namespace App\Providers;

use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('storefront.partials.header', function ($view) {
            $view->with('headerCategories', Cache::remember(
                'storefront.header.categories',
                60,
                fn () => app(ProductsApi::class)->categories()
            ));
        });
    }
}

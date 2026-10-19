<?php

namespace App\Providers;

use App\Support\StorefrontCategories;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(StorefrontCategories::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['storefront.partials.header', 'livewire.storefront.product-listing'], function ($view) {
            $view->with('headerCategories', app(StorefrontCategories::class)->all());
        });
    }
}

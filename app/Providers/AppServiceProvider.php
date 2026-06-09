<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Models\Payment;
use App\Models\Shipment;
use App\Observers\PaymentObserver;
use App\Observers\ShipmentObserver;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            LoginContract::class,
            LoginResponse::class
        );
        
        $loader = AliasLoader::getInstance();
        $loader->alias('Paystack',\Unicodeveloper\Paystack\Facades\Paystack::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        Shipment::observe(ShipmentObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}

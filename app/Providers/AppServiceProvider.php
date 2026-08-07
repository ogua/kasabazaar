<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Models\Payment;
use App\Models\Shipment;
use App\Observers\PaymentObserver;
use App\Observers\ShipmentObserver;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginContract;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

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
        $loader->alias('Paystack', \Unicodeveloper\Paystack\Facades\Paystack::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        Shipment::observe(ShipmentObserver::class);
        Payment::observe(PaymentObserver::class);

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');

            return "{$frontendUrl}/reset-password.php?token={$token}&email=".urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}

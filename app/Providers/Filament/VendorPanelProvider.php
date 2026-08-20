<?php

namespace App\Providers\Filament;

use App\Filament\Vendor\Pages\Auth\Login;
use App\Filament\Vendor\Pages\Overview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class VendorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('vendor')
            ->path('vendor-portal')
            ->login(Login::class)
            ->brandName(config('app.name').' Vendor Portal')
            ->brandLogo(asset('images/brand/logo-lockup.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/favicon-32x32.png'))
            ->colors([
                // Sampled from the KASAROSE logo: blue #055F9C, red #EF4136.
                'primary' => Color::hex('#055F9C'),
                'danger' => Color::hex('#EF4136'),
            ])
            ->discoverPages(in: app_path('Filament/Vendor/Pages'), for: 'App\Filament\Vendor\Pages')
            ->pages([
                Overview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

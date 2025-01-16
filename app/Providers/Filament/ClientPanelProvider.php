<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Swis\Filament\Backgrounds\ImageProviders\MyImages;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class ClientPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
        ->id('client')
        ->path('client')
        ->login()
        ->profile()
        ->sidebarCollapsibleOnDesktop()
        ->brandLogo(asset('images/kasabazaar-logo.png'))
        ->brandLogoHeight('4rem')
        ->favicon(asset('images/kasabazaar-logo.png'))
        //->spa()
        ->colors([
            'primary' => Color::hex('#A0043C'),
            'info' => Color::hex('#003151'),
            ])
            ->discoverResources(in: app_path('Filament/Client/Resources'), for: 'App\\Filament\\Client\\Resources')
            ->discoverPages(in: app_path('Filament/Client/Pages'), for: 'App\\Filament\\Client\\Pages')
            ->pages([
                Pages\Dashboard::class,
                ])
                ->discoverWidgets(in: app_path('Filament/Client/Widgets'), for: 'App\\Filament\\Client\\Widgets')
                ->widgets([
                    Widgets\AccountWidget::class,
                    ])
                    ->plugins([
                        \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                        FilamentBackgroundsPlugin::make()
                        ->imageProvider(
                            MyImages::make()
                                ->directory('images/backgrounds')
                        ),
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

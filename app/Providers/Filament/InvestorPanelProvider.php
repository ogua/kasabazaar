<?php

namespace App\Providers\Filament;

use App\Filament\Investor\Widgets\ContainerProfiInvestortWidget;
use App\Filament\Widgets\ClientGrowthChart;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\ShipmentsByContainerChart;
use App\Filament\Widgets\TopStatesWidget;
use App\Http\Middleware\RedirectToAdminPanel;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Swis\Filament\Backgrounds\ImageProviders\MyImages;

class InvestorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('investor')
            ->path('investor')
            ->login()
            ->profile()
            ->sidebarCollapsibleOnDesktop()
            ->brandLogo(asset('images/kasabazaar-logo.png'))
            ->brandLogoHeight('4rem')
            ->favicon(asset('images/kasabazaar-logo.png'))
            ->colors([
                'primary' => Color::hex('#A0043C'),
                'info' => Color::hex('#003151'),
            ])
            ->discoverResources(in: app_path('Filament/Investor/Resources'), for: 'App\\Filament\\Investor\\Resources')
            ->discoverPages(in: app_path('Filament/Investor/Pages'), for: 'App\\Filament\\Investor\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Investor/Widgets'), for: 'App\\Filament\\Investor\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                FinancialOverviewWidget::class,
                ContainerProfiInvestortWidget::class,
                TopStatesWidget::class,
                ShipmentsByContainerChart::class,
                ClientGrowthChart::class,

            ])
            ->viteTheme('resources/css/filament/investor/theme.css')
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
                RedirectToAdminPanel::class,
            ])
            ->renderHook(PanelsRenderHook::BODY_START, fn (): View => view('admin.impersonation-banner'));
    }
}

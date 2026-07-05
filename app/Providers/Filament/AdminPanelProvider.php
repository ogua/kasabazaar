<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\RegisterBranch;
use App\Models\Branch;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
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
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;
use SolutionForest\FilamentSimpleLightBox\SimpleLightBoxPlugin;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Swis\Filament\Backgrounds\ImageProviders\MyImages;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->sidebarCollapsibleOnDesktop()
            ->tenant(Branch::class)
            ->tenantRegistration(RegisterBranch::class)
            ->brandLogo(asset('images/kasabazaar-logo.png'))
            ->brandLogoHeight('4rem')
            ->favicon(asset('images/kasabazaar-logo.png'))
            // ->spa()
            ->colors([
                'primary' => Color::hex('#A0043C'),
                'info' => Color::hex('#003151'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
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
            ])
            ->tenantMiddleware([
                \BezhanSalleh\FilamentShield\Middleware\SyncShieldTenant::class,
            ], isPersistent: true)
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                FilamentBackgroundsPlugin::make()
                    ->imageProvider(
                        MyImages::make()
                            ->directory('images/backgrounds')
                    ),
                SimpleLightBoxPlugin::make(),
                FilamentEditProfilePlugin::make()
                    ->slug('my-profile')
                    ->setTitle('Profile')
                    ->setNavigationLabel('Profile')
                    // ->setNavigationGroup('Group Profile')
                    ->setIcon('heroicon-o-user')
                    ->setSort(10)
                   // ->canAccess(fn () => Auth::user()->id === 1)
                    ->shouldRegisterNavigation(false)
                    ->shouldShowDeleteAccountForm(false)
                    // ->shouldShowSanctumTokens()
                    ->shouldShowBrowserSessionsForm()
                    ->shouldShowAvatarForm(),
            ])
            ->navigationGroups([
                'Cashbook',
                'Fleet Management',
                'Finance',
                'Payroll',
                'Staff Management',
                'Messaging',
                'Customer Feedback',
                'Reports',
                'Website',
                'E-Commerce',
                'Investors',
                'Roles & Permissions',

            ])
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn (): View => view('admin.staff-login-btn'))
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn () => Auth::user()->name)
                    ->url(fn (): string => EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle'),
                // //If you are using tenancy need to check with the visible method where ->company() is the relation between the user and tenancy model as you called
                // ->visible(function (): bool {
                //     return Auth::user()->company()->exists();
                // }),
            ]);
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Security at a glance on the Activity Log page. Not on the dashboard.
 */
class AuditSecurityStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $failedLogins = Activity::query()->where('event', 'login_failed')->where('created_at', '>=', now()->subDay())->count();
        $loginsToday = Activity::query()->whereIn('event', ['login', 'login_via_remember'])->whereDate('created_at', today())->count();
        $highRisk = Activity::query()->where('risk', Activity::RISK_HIGH)->where('created_at', '>=', now()->subDays(7))->count();
        $deletions = Activity::query()->where('event', 'deleted')->where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Failed sign-ins (24h)', number_format($failedLogins))
                ->color($failedLogins > 10 ? 'danger' : 'gray')
                ->icon('heroicon-o-shield-exclamation'),
            Stat::make('Sign-ins today', number_format($loginsToday))
                ->icon('heroicon-o-arrow-right-on-rectangle'),
            Stat::make('High-risk changes (7 days)', number_format($highRisk))
                ->color($highRisk > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-exclamation-triangle'),
            Stat::make('Deletions (7 days)', number_format($deletions))
                ->icon('heroicon-o-trash'),
        ];
    }
}

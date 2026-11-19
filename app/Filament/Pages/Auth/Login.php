<?php

namespace App\Filament\Pages\Auth;

use App\Services\Audit\Audit;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;

/**
 * Filament's sign-in page, plus an audit entry when someone is locked out for too many
 * attempts (Filament throttles without firing Laravel's Lockout event). Successful and failed
 * sign-ins are recorded by App\Listeners\AuditAuthEvents. Used by all three panels.
 */
class Login extends BaseLogin
{
    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        $attempted = (string) ($this->data['email'] ?? '');

        Audit::security(
            'login_throttled',
            'Sign-in locked for '.$exception->secondsUntilAvailable.'s after too many attempts'.($attempted !== '' ? " for {$attempted}" : ''),
            null,
            ['attempted' => $attempted, 'seconds_locked' => $exception->secondsUntilAvailable],
            true,
        );

        return parent::getRateLimitedNotification($exception);
    }
}

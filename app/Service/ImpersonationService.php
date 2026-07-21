<?php

namespace App\Service;

use App\Enums\UserStatus;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class ImpersonationService
{
    public static function isImpersonating(): bool
    {
        return session()->has('impersonate.original_id');
    }

    /**
     * Only a genuine super-admin session (never one that's already impersonating)
     * may start impersonating — prevents chaining/nesting impersonation.
     */
    public static function canImpersonate(): bool
    {
        return (auth()->user()?->hasRole('super_admin') ?? false) && ! self::isImpersonating();
    }

    /**
     * Start impersonating $target, stashing the current admin's identity in the
     * session so it can be restored later. Returns the panel path the caller
     * should redirect to.
     *
     * @throws \RuntimeException if $target is inactive or impersonation is already active
     */
    public static function start(User $target): string
    {
        if (self::isImpersonating()) {
            throw new \RuntimeException('You are already impersonating a user — stop first before switching.');
        }

        if ($target->status !== UserStatus::Active) {
            throw new \RuntimeException('Cannot impersonate an inactive user.');
        }

        session([
            'impersonate.original_id' => auth()->id(),
            'impersonate.original_panel' => Filament::getCurrentPanel()?->getId() ?? 'admin',
        ]);

        Auth::loginUsingId($target->id);
        self::refreshPasswordHashInSession($target);

        return self::targetPanelPath($target);
    }

    /**
     * Auth::loginUsingId() rotates the session ID but does not clear the
     * previous user's 'password_hash_{guard}' key from the carried-over session
     * attributes. AuthenticateSession middleware (run again on the target panel)
     * compares that stale hash against the new user's actual password and, on
     * a mismatch, force-logs-out and flushes the session — so every impersonation
     * swap must refresh this key to the new user's own hash immediately.
     */
    public static function refreshPasswordHashInSession(User $user): void
    {
        session()->put('password_hash_'.auth()->getDefaultDriver(), $user->getAuthPassword());
    }

    /**
     * A short-lived signed link to the plain-controller endpoint that actually
     * performs the login swap. Routing the swap through a real, synchronous
     * request (rather than a Livewire AJAX action) guarantees the session-ID
     * regeneration in start() is fully committed and cookied before the
     * browser's follow-up navigation to the target panel.
     */
    public static function startUrl(User $target): string
    {
        return URL::temporarySignedRoute('impersonate.start', now()->addMinute(), ['user' => $target->id]);
    }

    public static function targetPanelId(User $user): string
    {
        if (filled($user->investor_id)) {
            return 'investor';
        }
        if (filled($user->client_id)) {
            return 'client';
        }

        return 'admin';
    }

    public static function targetPanelPath(User $user): string
    {
        return '/'.self::targetPanelId($user);
    }
}

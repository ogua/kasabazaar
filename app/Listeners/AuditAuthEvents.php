<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Audit\Audit;
use App\Services\Audit\AuditContext;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Sign-ins, failed sign-ins and sign-outs through Laravel's auth (all three Filament panels and
 * the staff mobile API). The customer / investor / vendor API logins check passwords themselves
 * and call App\Services\Audit\Audit directly.
 *
 * Impersonation swaps and deliberate "sign the inactive user straight back out" logouts run
 * inside AuditContext::suppressAuthEvents(); the code doing them records the real event.
 */
class AuditAuthEvents
{
    /** A user Filament is about to sign straight back out; their logout is not news. */
    private static ?string $refusedUserId = null;

    public function __construct(private AuditContext $context) {}

    public function handleLogin(Login $event): void
    {
        if ($this->context->authEventsSuppressed()) {
            return;
        }

        $user = $event->user instanceof Model ? $event->user : null;
        $panel = Filament::getCurrentPanel();

        // Filament signs a user straight back out when they may not use this panel (inactive
        // account, wrong portal). Record that once, as a refusal, not as "signed in / signed out".
        if ($panel !== null && $event->user instanceof FilamentUser && ! $event->user->canAccessPanel($panel)) {
            self::$refusedUserId = (string) $event->user->getAuthIdentifier();

            Audit::security(
                'login_blocked',
                ($this->nameOf($event->user) ?? 'A user').' was refused sign-in'.$this->where().' (no access to this panel or account inactive)',
                $user,
                ['guard' => $event->guard, 'panel' => $panel->getId()],
                causer: $user,
            );

            return;
        }

        Audit::security(
            $event->remember ? 'login_via_remember' : 'login',
            ($this->nameOf($event->user) ?? 'A user').' signed in'.$this->where(),
            $user,
            ['guard' => $event->guard],
            causer: $user,
        );
    }

    public function handleFailed(Failed $event): void
    {
        if ($this->context->authEventsSuppressed()) {
            return;
        }

        $identifier = (string) ($event->credentials['email'] ?? $event->credentials['phone'] ?? $event->credentials['username'] ?? '');
        $user = $event->user instanceof Model ? $event->user : null;

        Audit::security(
            'login_failed',
            'Failed sign-in'.(filled($identifier) ? " for {$identifier}" : '').$this->where(),
            $user,
            [
                'attempted' => $identifier,
                'reason' => $user ? 'wrong_password' : 'unknown_account',
                'guard' => $event->guard,
            ],
        );
    }

    public function handleLogout(Logout $event): void
    {
        if ($this->context->authEventsSuppressed() || $event->user === null) {
            return;
        }

        if (self::$refusedUserId !== null && self::$refusedUserId === (string) $event->user->getAuthIdentifier()) {
            self::$refusedUserId = null;

            return;
        }

        $user = $event->user instanceof Model ? $event->user : null;

        Audit::security(
            'logout',
            ($this->nameOf($event->user) ?? 'A user').' signed out'.$this->where(),
            $user,
            ['guard' => $event->guard],
            causer: $user,
        );
    }

    public function handlePasswordResetLinkSent(PasswordResetLinkSent $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        Audit::security(
            'password_reset_requested',
            'Password reset link sent to '.($user?->email ?? 'a user'),
            $user,
        );
    }

    private function nameOf(?Authenticatable $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return $user instanceof Model ? ($user->getAttribute('name') ?? $user->getAttribute('email')) : null;
    }

    private function where(): string
    {
        return match ($this->context->channel()) {
            'admin' => ' to the admin panel',
            'client' => ' to the client portal',
            'investor' => ' to the investor portal',
            'api' => ' via the mobile app',
            default => '',
        };
    }
}

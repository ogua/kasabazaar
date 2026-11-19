<?php

namespace App\Services\Audit;

use App\Models\User;

/**
 * Sign-in events for the mobile/API logins that check passwords themselves (customer,
 * investor, vendor, staff apps). Panel logins are recorded by App\Listeners\AuditAuthEvents.
 */
class AuthAudit
{
    public static function loginSucceeded(User $user, string $app): void
    {
        Audit::security('login', "{$user->name} signed in to the {$app}", $user, ['app' => $app], causer: $user);
    }

    public static function loginFailed(string $identifier, ?User $user, string $app, string $reason = 'wrong_password'): void
    {
        Audit::security(
            'login_failed',
            "Failed sign-in to the {$app} for {$identifier}",
            $user,
            ['app' => $app, 'attempted' => $identifier, 'reason' => $user ? $reason : 'unknown_account'],
        );
    }

    public static function loginBlocked(User $user, string $app, string $reason): void
    {
        Audit::security(
            'login_blocked',
            "{$user->name} was refused sign-in to the {$app} ({$reason})",
            $user,
            ['app' => $app, 'reason' => $reason],
            causer: $user,
        );
    }

    public static function loggedOut(?User $user, string $app): void
    {
        if ($user === null) {
            return;
        }

        Audit::security('logout', "{$user->name} signed out of the {$app}", $user, ['app' => $app], causer: $user);
    }

    public static function tokensRevoked(User $user, string $reason): void
    {
        Audit::security('tokens_revoked', "All app sessions of {$user->name} were signed out ({$reason})", $user, ['reason' => $reason], true);
    }

    public static function passwordResetCompleted(User $user, string $app): void
    {
        Audit::security('password_reset_completed', "{$user->name} reset their password from the {$app}", $user, ['app' => $app], true, $user);
    }

    public static function accountDeleted(User $user, string $app): void
    {
        Audit::security('account_deleted', "{$user->name} deleted their account from the {$app}", $user, ['app' => $app, 'email' => $user->email], true, $user);
    }
}

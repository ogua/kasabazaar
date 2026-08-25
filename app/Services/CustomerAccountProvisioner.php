<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Creates the mobile-app login that backs a staff-created Client.
 *
 * The password is random and never disclosed to anyone: the client claims the
 * account through a reset link instead. That matters because these accounts now
 * gate saved delivery addresses, order history and one-tap Paystack checkout —
 * a shared default password would hand any of that to anyone who guessed it.
 */
class CustomerAccountProvisioner
{
    public const CREATED_INVITED = 'created_invited';

    public const CREATED_NO_EMAIL = 'created_no_email';

    public const ALREADY_EXISTS = 'already_exists';

    public const NO_LOGIN_IDENTIFIER = 'no_login_identifier';

    /**
     * @return array{status: string, user: ?User}
     */
    public function provision(Client $client, ?string $branchId): array
    {
        // The users table keys logins off `email`, so a client with only a phone
        // number is registered under it. Such an account cannot be claimed by
        // email, which the caller surfaces to staff.
        $login = $client->email ?: $client->phone;

        if (! $login) {
            return ['status' => self::NO_LOGIN_IDENTIFIER, 'user' => null];
        }

        $existing = User::where('email', $login)->first();

        if ($existing) {
            return ['status' => self::ALREADY_EXISTS, 'user' => $existing];
        }

        $user = User::create([
            'name' => $client->name,
            'email' => $login,
            'phone' => $client->phone,
            // Deliberately unguessable and never shown. Replaced by the client
            // via the reset link below, or via Forgot Password in the app.
            'password' => Hash::make(Str::random(48)),
            'role' => 'customer',
            'branch_id' => $branchId,
            'client_id' => $client->id,
        ]);

        if (! $client->email) {
            return ['status' => self::CREATED_NO_EMAIL, 'user' => $user];
        }

        // Mail failures must not roll back a client that was created correctly;
        // staff can re-send from the client record.
        try {
            Password::broker()->sendResetLink(['email' => $client->email]);
        } catch (\Throwable $e) {
            report($e);

            return ['status' => self::CREATED_NO_EMAIL, 'user' => $user];
        }

        return ['status' => self::CREATED_INVITED, 'user' => $user];
    }
}

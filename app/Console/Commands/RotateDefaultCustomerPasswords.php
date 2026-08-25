<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Remediation for client and investor logins that were provisioned with the
 * shared default password 'password'. Those accounts now gate payment history,
 * saved delivery addresses, in-app checkout and investment balances, so every
 * one of them is treated as compromised until its password is rotated.
 *
 * Run once after deploying the provisioning fix:
 *   php artisan app:rotate-default-customer-passwords --dry-run
 *   php artisan app:rotate-default-customer-passwords
 */
class RotateDefaultCustomerPasswords extends Command
{
    protected $signature = 'app:rotate-default-customer-passwords
                            {--dry-run : List affected accounts without changing anything}
                            {--no-invite : Rotate passwords but do not send reset emails}';

    protected $description = 'Rotate customer and investor logins still using the default password and invite them to set a new one.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $invite = ! $this->option('no-invite');

        // Only self-service personas were ever given the default; staff logins
        // are created through a different path and are left alone.
        $candidates = User::query()
            ->where(fn ($q) => $q->whereNotNull('client_id')->orWhereNotNull('investor_id'))
            ->get();

        $affected = $candidates->filter(
            fn (User $user) => $user->password && Hash::check('password', $user->password)
        );

        if ($affected->isEmpty()) {
            $this->info('No accounts are using the default password.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d account(s) are using the default password:', $affected->count()));
        foreach ($affected as $user) {
            $this->line(sprintf('  - %s (%s)', $user->email, $user->client_id ? 'client' : 'investor'));
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry run: nothing was changed.');

            return self::SUCCESS;
        }

        $rotated = 0;
        $invited = 0;
        $failed = 0;

        foreach ($affected as $user) {
            // Rotate first, so the account is secured even if the invite fails.
            $user->forceFill(['password' => Hash::make(Str::random(48))])->save();
            // Existing API tokens were issued under the compromised password.
            $user->tokens()->delete();
            $rotated++;

            if (! $invite || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            try {
                Password::broker()->sendResetLink(['email' => $user->email]);
                $invited++;
            } catch (\Throwable $e) {
                report($e);
                $failed++;
                $this->error(sprintf('  Could not email %s: %s', $user->email, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf('Rotated %d password(s); sent %d reset invite(s).', $rotated, $invited));

        if ($failed > 0) {
            $this->warn(sprintf('%d invite(s) failed to send. Those users must use Forgot Password.', $failed));
        }

        $this->warn('Accounts registered under a phone number have no email and cannot self-recover; contact them directly.');

        return self::SUCCESS;
    }
}

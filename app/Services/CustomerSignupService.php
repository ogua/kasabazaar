<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Client;
use App\Models\CustomerSignupVerification;
use App\Models\User;
use App\Service\NotificationService;
use App\Service\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Self-service signup from the customer app.
 *
 * Someone who has shipped with us before already has a client record that staff
 * created. Signing up with that record's email or phone would otherwise make a
 * second client, and their past shipments would never show in the app. Instead we
 * send a one-time code to the contact *on that record* and only link the new login
 * to it once the code comes back, so nobody can claim another person's history by
 * typing their phone number.
 */
class CustomerSignupService
{
    public const CODE_TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const MAX_SENDS = 3;

    /**
     * An existing client (with no app login yet) whose email or phone matches.
     */
    public function findClaimableClient(string $email, ?string $phone): ?Client
    {
        $hasLogin = fn ($query) => $query->whereNotIn('id', User::whereNotNull('client_id')->select('client_id'));

        $byEmail = Client::query()
            ->where($hasLogin)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->oldest()
            ->first();

        if ($byEmail) {
            return $byEmail;
        }

        $lastNine = self::lastNineDigits($phone);

        if ($lastNine === '') {
            return null;
        }

        return Client::query()
            ->where($hasLogin)
            ->where('phone', 'like', '%'.substr($lastNine, -4))
            ->oldest()
            ->get()
            ->first(fn (Client $client) => self::lastNineDigits($client->phone) === $lastNine);
    }

    /**
     * @param  array{name: string, email: string, phone?: ?string, password: string, whatsapp_opt_in?: bool}  $data
     */
    public function startVerification(Client $client, array $data): CustomerSignupVerification
    {
        $channel = filled($client->phone) ? 'sms' : 'email';
        $code = $this->newCode();

        $verification = CustomerSignupVerification::create([
            'client_id' => $client->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'whatsapp_opt_in' => (bool) ($data['whatsapp_opt_in'] ?? false),
            'code_hash' => Hash::make($code),
            'channel' => $channel,
            'destination' => $channel === 'sms' ? $client->phone : $client->email,
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        $this->sendCode($verification, $code);

        return $verification;
    }

    public function resend(CustomerSignupVerification $verification): bool
    {
        if ($verification->verified_at || $verification->sends >= self::MAX_SENDS) {
            return false;
        }

        $code = $this->newCode();

        $verification->update([
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'sends' => $verification->sends + 1,
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        $this->sendCode($verification, $code);

        return true;
    }

    /**
     * Check the code and, when it matches, create the login linked to the client.
     *
     * @throws \InvalidArgumentException with a message safe to show the user
     */
    public function verify(CustomerSignupVerification $verification, string $code): User
    {
        if (! $verification->isOpen()) {
            throw new \InvalidArgumentException('This code has expired. Request a new one.');
        }

        if ($verification->attempts >= self::MAX_ATTEMPTS) {
            throw new \InvalidArgumentException('Too many wrong codes. Request a new one.');
        }

        if (! Hash::check($code, $verification->code_hash)) {
            $verification->increment('attempts');

            throw new \InvalidArgumentException('That code is not right. Check it and try again.');
        }

        if (User::where('email', $verification->email)->exists()) {
            throw new \InvalidArgumentException('An account with this email already exists. Sign in instead.');
        }

        return DB::transaction(function () use ($verification) {
            $client = Client::lockForUpdate()->findOrFail($verification->client_id);

            if (User::where('client_id', $client->id)->exists()) {
                throw new \InvalidArgumentException('This customer record already has an account. Sign in instead.');
            }

            $user = User::create([
                'name' => $verification->name,
                'email' => $verification->email,
                'phone' => $verification->phone ?? $client->phone,
                'password' => $verification->password,
                'client_id' => $client->id,
                'branch_id' => $client->branch_id,
                'role' => 'customer',
            ]);

            if (blank($client->email)) {
                $client->update(['email' => $verification->email]);
            }

            if ($verification->whatsapp_opt_in) {
                $user->setWhatsappConsent(true);
                $client->setWhatsappConsent(true);
            }

            $verification->update(['verified_at' => now()]);

            return $user;
        });
    }

    /**
     * The branch a brand-new app client belongs to when they did not pick one.
     */
    public static function defaultBranchId(): ?string
    {
        $configured = SystemSetting::get('default_customer_branch_id');

        if ($configured && Branch::whereKey($configured)->exists()) {
            return $configured;
        }

        return Branch::query()->oldest()->value('id');
    }

    public static function lastNineDigits(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) >= 9 ? substr($digits, -9) : '';
    }

    private function newCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendCode(CustomerSignupVerification $verification, string $code): void
    {
        $message = "Your KASAROSE verification code is {$code}. It expires in ".self::CODE_TTL_MINUTES.' minutes. Do not share it.';

        try {
            if ($verification->channel === 'sms') {
                NotificationService::sendSmsToSender($verification->destination, $message);
            } else {
                Mail::raw($message, fn ($mail) => $mail->to($verification->destination)->subject('Your KASAROSE verification code'));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

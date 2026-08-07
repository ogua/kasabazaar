<?php

namespace App\Services\Kasabazaar;

class AuthApi
{
    public function __construct(protected KasabazaarClient $client) {}

    public function loginCustomer(string $email, string $password, ?string $guestSessionId = null): array
    {
        return $this->client->post('customer/auth/login', ['email' => $email, 'password' => $password], $this->guestHeader($guestSessionId))->data;
    }

    public function registerCustomer(array $data, ?string $guestSessionId = null): array
    {
        return $this->client->post('customer/auth/register', $data, $this->guestHeader($guestSessionId))->data;
    }

    public function me(): array
    {
        return $this->client->get('customer/auth/me')->data;
    }

    public function updateProfile(array $data): array
    {
        return $this->client->put('customer/auth/profile', $data)->data;
    }

    public function changePassword(string $currentPassword, string $newPassword, string $newPasswordConfirmation): void
    {
        $this->client->put('customer/auth/password', [
            'current_password' => $currentPassword,
            'password' => $newPassword,
            'password_confirmation' => $newPasswordConfirmation,
        ]);
    }

    public function forgotPasswordCustomer(string $email): void
    {
        $this->client->post('customer/auth/forgot-password', ['email' => $email]);
    }

    public function resetPasswordCustomer(array $data): void
    {
        $this->client->post('customer/auth/reset-password', $data);
    }

    public function loginVendor(string $email, string $password): array
    {
        return $this->client->post('marketplace/vendor/auth/login', ['email' => $email, 'password' => $password])->data;
    }

    public function forgotPasswordVendor(string $email): void
    {
        $this->client->post('marketplace/vendor/auth/forgot-password', ['email' => $email]);
    }

    public function resetPasswordVendor(array $data): void
    {
        $this->client->post('marketplace/vendor/auth/reset-password', $data);
    }

    private function guestHeader(?string $guestSessionId): array
    {
        return $guestSessionId ? ['X-Guest-Session-Id' => $guestSessionId] : [];
    }
}

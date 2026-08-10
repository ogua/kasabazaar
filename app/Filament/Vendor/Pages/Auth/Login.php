<?php

namespace App\Filament\Vendor\Pages\Auth;

use App\Models\User;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\VendorApi;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $credentials = $this->getCredentialsFromFormData($this->form->getState());

        try {
            $result = app(VendorApi::class)->login($credentials['email'], $credentials['password']);
        } catch (KasabazaarApiException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            return null;
        }

        $user = User::updateOrCreate(
            ['kasabazaar_user_id' => $result['user']['id']],
            [
                'name' => $result['user']['name'],
                'email' => $result['user']['email'],
                'role' => 'vendor',
                'vendor_id' => $result['vendor']['id'] ?? null,
                'kasabazaar_token' => $result['token'],
            ]
        );

        Filament::auth()->login($user, remember: true);

        session()->regenerate();

        return app(LoginResponse::class);
    }
}

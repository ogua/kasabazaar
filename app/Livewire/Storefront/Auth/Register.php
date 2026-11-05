<?php

namespace App\Livewire\Storefront\Auth;

use App\Models\User;
use App\Services\Kasabazaar\AuthApi;
use App\Services\Kasabazaar\CartApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $error = '';

    /** Set when the email or phone matched an existing shipping customer. */
    public ?string $verificationId = null;

    public string $maskedDestination = '';

    public string $code = '';

    public string $notice = '';

    public function submit(AuthApi $authApi): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8|same:password_confirmation',
        ]);

        try {
            $result = $authApi->registerCustomer([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ], CartApi::guestToken());
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();

            return;
        }

        if (! empty($result['requires_verification'])) {
            $this->error = '';
            $this->verificationId = $result['verification_id'];
            $this->maskedDestination = $result['masked_destination'] ?? '';

            return;
        }

        $this->signIn($result);
    }

    public function verify(AuthApi $authApi): void
    {
        $this->validate(['code' => 'required|digits:6']);

        try {
            $result = $authApi->verifySignup((string) $this->verificationId, $this->code, CartApi::guestToken());
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->signIn($result);
    }

    public function resend(AuthApi $authApi): void
    {
        try {
            $authApi->resendSignupCode((string) $this->verificationId);
            $this->error = '';
            $this->notice = 'A new code is on its way.';
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    /**
     * @param  array{token: string, user: array<string, mixed>}  $result
     */
    private function signIn(array $result): void
    {
        $user = User::updateOrCreate(
            ['kasabazaar_user_id' => $result['user']['id']],
            [
                'name' => $result['user']['name'],
                'email' => $result['user']['email'],
                'role' => 'customer',
                'kasabazaar_token' => $result['token'],
            ]
        );

        Auth::login($user, remember: true);
        session()->regenerate();
        session()->forget(CartApi::GUEST_TOKEN_KEY);

        $this->redirect(route('storefront.account.dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.storefront.auth.register')->layout('storefront.layouts.app', ['title' => 'Register']);
    }
}

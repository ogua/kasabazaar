<?php

namespace App\Livewire\Storefront\Auth;

use App\Models\User;
use App\Services\Kasabazaar\AuthApi;
use App\Services\Kasabazaar\CartApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public string $error = '';

    public function submit(AuthApi $authApi): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $result = $authApi->loginCustomer($this->email, $this->password, CartApi::guestToken());
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();

            return;
        }

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

        // Back to where they were (e.g. "sign in to bid"), else the dashboard.
        $this->redirectIntended(route('storefront.account.dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.storefront.auth.login')->layout('storefront.layouts.app', ['title' => 'Sign In']);
    }
}

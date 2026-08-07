<?php

namespace App\Livewire\Storefront\Auth;

use App\Services\Kasabazaar\AuthApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Livewire\Component;

class ResetPassword extends Component
{
    public string $token;

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $error = '';

    public bool $done = false;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function submit(AuthApi $authApi): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8|same:password_confirmation',
        ]);

        try {
            $authApi->resetPasswordCustomer([
                'token' => $this->token,
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ]);
            $this->done = true;
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.storefront.auth.reset-password')->layout('storefront.layouts.app', ['title' => 'Reset Password']);
    }
}

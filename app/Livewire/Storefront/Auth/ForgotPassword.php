<?php

namespace App\Livewire\Storefront\Auth;

use App\Services\Kasabazaar\AuthApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    public string $error = '';

    public bool $sent = false;

    public function submit(AuthApi $authApi): void
    {
        $this->validate(['email' => 'required|email']);

        try {
            $authApi->forgotPasswordCustomer($this->email);
            $this->sent = true;
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.storefront.auth.forgot-password')->layout('storefront.layouts.app', ['title' => 'Forgot Password']);
    }
}

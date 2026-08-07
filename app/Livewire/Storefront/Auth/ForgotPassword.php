<?php

namespace App\Livewire\Storefront\Auth;

use App\Services\Kasabazaar\AuthApi;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function submit(AuthApi $authApi): void
    {
        $this->validate(['email' => 'required|email']);

        $authApi->forgotPasswordCustomer($this->email);

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.storefront.auth.forgot-password')->layout('storefront.layouts.app', ['title' => 'Forgot Password']);
    }
}

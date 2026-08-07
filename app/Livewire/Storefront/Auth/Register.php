<?php

namespace App\Livewire\Storefront\Auth;

use App\Models\User;
use App\Services\Kasabazaar\AuthApi;
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
            ], session()->getId());
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

        $this->redirect(route('storefront.account.dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.storefront.auth.register')->layout('storefront.layouts.app', ['title' => 'Register']);
    }
}

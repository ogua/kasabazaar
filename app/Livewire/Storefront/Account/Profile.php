<?php

namespace App\Livewire\Storefront\Account;

use App\Services\Kasabazaar\AuthApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public string $profileMessage = '';

    public string $passwordMessage = '';

    public string $passwordError = '';

    public function mount(AuthApi $authApi): void
    {
        $me = $authApi->me();
        $this->name = $me['name'];
        $this->phone = $me['phone'] ?? '';
    }

    public function updateProfile(AuthApi $authApi): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $authApi->updateProfile(['name' => $this->name, 'phone' => $this->phone]);

        Auth::user()->update(['name' => $this->name]);
        $this->profileMessage = 'Profile updated.';
    }

    public function updatePassword(AuthApi $authApi): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|same:new_password_confirmation',
        ]);

        try {
            $authApi->changePassword($this->current_password, $this->new_password, $this->new_password_confirmation);
            $this->passwordMessage = 'Password changed.';
            $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        } catch (KasabazaarApiException $e) {
            $this->passwordError = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.storefront.account.profile')->layout('storefront.layouts.app', ['title' => 'Account Details']);
    }
}

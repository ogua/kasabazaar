<?php

namespace App\Livewire\Storefront\Account;

use App\Services\Kasabazaar\AuthApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public string $profileMessage = '';

    public string $profileError = '';

    public string $passwordMessage = '';

    public string $passwordError = '';

    public function mount(AuthApi $authApi): void
    {
        try {
            $me = $authApi->me();
            $this->name = $me['name'];
            $this->phone = $me['phone'] ?? '';
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.account.profile: failed to load profile', ['message' => $e->getMessage()]);
            $this->name = Auth::user()->name;
            $this->profileError = 'We\'re having trouble loading your latest profile details. Showing your last known name.';
        }
    }

    public function updateProfile(AuthApi $authApi): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        try {
            $authApi->updateProfile(['name' => $this->name, 'phone' => $this->phone]);

            Auth::user()->update(['name' => $this->name]);
            $this->profileMessage = 'Profile updated.';
            $this->profileError = '';
        } catch (KasabazaarApiException $e) {
            $this->profileError = $e->getMessage();
        }
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

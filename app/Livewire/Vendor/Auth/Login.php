<?php

namespace App\Livewire\Vendor\Auth;

use App\Models\User;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\VendorApi;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public string $error = '';

    public function submit(VendorApi $vendorApi): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $result = $vendorApi->login($this->email, $this->password);
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();

            return;
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

        Auth::login($user, remember: true);
        session()->regenerate();

        $this->redirect(route('vendor.dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.vendor.auth.login');
    }
}

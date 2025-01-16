<?php

namespace App\Http\Responses;

use App\Filament\Customer\Resources\MyOrdersResource;
use App\Filament\Resources\OrderResource;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Filament\Http\Responses\Auth\LoginResponse as Authloginresponse;

class LoginResponse extends Authloginresponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        //$role = auth()->user()?->getRoleNames()?->first();

       $role = auth()->user()?->role;

        if ($role === 'customer') {
            return redirect()->to("/client");
        }else{
            return redirect()->to("/admin");
        }

        Filament::auth()->logout();

        session()->regenerate();

        return parent::toResponse($request);
    }
}
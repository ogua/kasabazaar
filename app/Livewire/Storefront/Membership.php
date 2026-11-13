<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\MembershipApi;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Membership extends Component
{
    public string $error = '';

    public function subscribe(MembershipApi $membershipApi, string $planId): void
    {
        if (! auth()->check()) {
            $this->redirect(route('storefront.login'), navigate: false);

            return;
        }

        try {
            $checkout = $membershipApi->subscribe($planId);
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirect($checkout['authorization_url'], navigate: false);
    }

    public function render(MembershipApi $membershipApi)
    {
        $plans = [];
        $membership = ['active' => null, 'history' => []];
        $loadError = null;

        try {
            $plans = $membershipApi->plans();

            if (auth()->check()) {
                $membership = $membershipApi->mine();
            }
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.membership: failed to load membership', ['message' => $e->getMessage()]);
            $loadError = 'We\'re having trouble loading memberships right now. Please try again shortly.';
        }

        return view('livewire.storefront.membership', [
            'plans' => $plans,
            'active' => $membership['active'] ?? null,
            'history' => $membership['history'] ?? [],
            'loadError' => $loadError,
        ])->layout('storefront.layouts.app')->title('Membership');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Service\ImpersonationService;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Performs the actual Auth::loginUsingId() swap as a plain, synchronous
     * request (reached via a signed redirect from the Filament UI) rather than
     * from inside a Livewire AJAX action — Livewire's partial-page update cycle
     * cannot reliably be trusted to propagate a mid-request session-ID
     * regeneration back to the browser before the follow-up full-page
     * navigation to the target panel, which left users logged out.
     */
    public function start(User $user)
    {
        abort_unless(ImpersonationService::canImpersonate(), 403);

        try {
            $path = ImpersonationService::start($user);
        } catch (\RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()->to($path);
    }

    public function stop()
    {
        $originalId = session('impersonate.original_id');

        abort_unless($originalId, 404);

        $panel = session('impersonate.original_panel', 'admin');

        session()->forget(['impersonate.original_id', 'impersonate.original_panel']);

        Auth::loginUsingId($originalId);
        ImpersonationService::refreshPasswordHashInSession(User::findOrFail($originalId));

        return redirect()->to('/'.$panel);
    }
}

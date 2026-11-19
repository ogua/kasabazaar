<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Service\ImpersonationService;
use App\Services\Audit\Audit;
use App\Services\Audit\AuditContext;
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

        $admin = auth()->user();

        try {
            $path = AuditContext::suppressAuthEvents(fn () => ImpersonationService::start($user));
        } catch (\RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        Audit::security(
            'impersonation_started',
            "{$admin->name} started impersonating {$user->name}",
            $user,
            ['admin_id' => $admin->getKey(), 'target_panel' => ImpersonationService::targetPanelId($user)],
            true,
            $admin,
        );

        return redirect()->to($path);
    }

    public function stop()
    {
        $originalId = session('impersonate.original_id');

        abort_unless($originalId, 404);

        $panel = session('impersonate.original_panel', 'admin');
        $impersonated = auth()->user();
        $admin = User::findOrFail($originalId);

        Audit::security(
            'impersonation_stopped',
            "{$admin->name} stopped impersonating ".($impersonated?->name ?? 'a user'),
            $impersonated,
            ['admin_id' => $admin->getKey()],
            true,
            $admin,
        );

        session()->forget(['impersonate.original_id', 'impersonate.original_panel']);

        AuditContext::suppressAuthEvents(fn () => Auth::loginUsingId($originalId));
        ImpersonationService::refreshPasswordHashInSession($admin);

        return redirect()->to('/'.$panel);
    }
}

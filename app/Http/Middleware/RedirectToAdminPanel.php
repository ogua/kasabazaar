<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectToAdminPanel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! filled($user->investor_id)) {
            return redirect()->to('/admin');
        }

        if ($user && $user->investor?->status !== 'active') {
            abort(403, 'Your investor account is currently inactive. Please contact us for assistance.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->status !== UserStatus::Active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated.',
            ], 401);
        }

        return $next($request);
    }
}

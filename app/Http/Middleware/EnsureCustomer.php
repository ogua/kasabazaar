<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$user->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Customer access only.',
            ], 403);
        }

        return $next($request);
    }
}

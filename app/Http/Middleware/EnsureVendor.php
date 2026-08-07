<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || ! $user->vendor_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Vendor access only.',
            ], 403);
        }

        if ($user->vendor?->status?->value !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your vendor account is currently inactive. Please contact support for assistance.',
            ], 403);
        }

        return $next($request);
    }
}

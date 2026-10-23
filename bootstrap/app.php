<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(\App\Http\Middleware\RedirectLegacyDomains::class);

        $middleware->alias([
            'customer' => \App\Http\Middleware\EnsureCustomer::class,
            'active' => \App\Http\Middleware\EnsureUserActive::class,
            'investor' => \App\Http\Middleware\EnsureInvestor::class,
            'vendor' => \App\Http\Middleware\EnsureVendor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // A webhook with a missing or forged signature is the sender's fault, not ours.
        $exceptions->render(fn (\Spatie\WebhookClient\Exceptions\InvalidWebhookSignature $e) => response()->json(['message' => $e->getMessage()], 401));
    })->create();

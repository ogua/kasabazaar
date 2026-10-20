<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends page visits on a retired domain (rddshipping.com) to the same path and query string on
 * kasarose.com, so old links, printed labels and search results keep working.
 *
 * Only GET/HEAD page requests move: the API and webhooks are still served on the old host so
 * older mobile builds and payment callbacks registered against it are not broken by a redirect.
 */
class RedirectLegacyDomains
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldRedirect($request)) {
            return $next($request);
        }

        $target = rtrim((string) config('group.legacy_redirect_url'), '/').$request->getRequestUri();

        return redirect()->away($target, 301);
    }

    private function shouldRedirect(Request $request): bool
    {
        $legacyHosts = array_map('strtolower', (array) config('group.legacy_domains', []));

        if (! in_array(strtolower($request->getHost()), $legacyHosts, true)) {
            return false;
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        return ! $request->is('api/*');
    }
}

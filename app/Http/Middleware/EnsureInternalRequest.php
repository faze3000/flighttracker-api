<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards /internal/* routes (parser callbacks). These are never meant to be
 * reachable from the public internet, so — on top of whatever network-level
 * restriction the deployment applies — every request must present the
 * shared secret configured for services.internal_api.key.
 */
class EnsureInternalRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.internal_api.key');

        if (empty($expected) || ! hash_equals($expected, (string) $request->header('X-Internal-Api-Key'))) {
            abort(403, 'Forbidden.');
        }

        return $next($request);
    }
}

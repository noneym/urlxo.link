<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Guards the site registry API with AUTH_HUB_ADMIN_TOKEN. */
class AuthHubAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('authhub.admin_token');
        if (strlen($expected) < 24) {
            return response()->json(['message' => 'Site registry API is disabled.'], 503);
        }

        if (! hash_equals($expected, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InternalToken
{
    private const SESSION_MARKER_KEY = '_yt_hub_internal_ok';

    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('app.internal_token', '');
        if ($expected === '') {
            abort(500, 'INTERNAL_TOKEN ist nicht gesetzt.');
        }

        // Once a caller has proved possession of the token in *any* way, bind
        // further requests to their session instead of requiring the raw token
        // in every URL (which leaks via referrers, logs, history).
        if ($request->hasSession() && $request->session()->get(self::SESSION_MARKER_KEY) === true) {
            return $next($request);
        }

        $provided = (string) ($request->header('X-Internal-Token') ?? $request->query('token', ''));
        if ($provided === '' || ! hash_equals($expected, $provided)) {
            abort(403);
        }

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_MARKER_KEY, true);
        }

        return $next($request);
    }
}

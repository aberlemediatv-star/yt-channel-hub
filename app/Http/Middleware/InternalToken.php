<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InternalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('app.internal_token', '');
        if ($expected === '') {
            abort(500, 'INTERNAL_TOKEN ist nicht gesetzt.');
        }

        $provided = (string) ($request->header('X-Internal-Token') ?? $request->query('token', ''));
        if (! hash_equals($expected, $provided)) {
            abort(403);
        }

        return $next($request);
    }
}

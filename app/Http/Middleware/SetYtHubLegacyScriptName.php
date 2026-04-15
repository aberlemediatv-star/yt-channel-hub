<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * YtHub\Lang und Admin-Navigation nutzen basename(SCRIPT_NAME) — unter Laravel ist das sonst /index.php.
 */
final class SetYtHubLegacyScriptName
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = ltrim($request->getPathInfo(), '/');
        if ($path !== '') {
            $_SERVER['SCRIPT_NAME'] = '/'.$path;
            $_SERVER['PHP_SELF'] = '/'.$path;
        }

        return $next($request);
    }
}

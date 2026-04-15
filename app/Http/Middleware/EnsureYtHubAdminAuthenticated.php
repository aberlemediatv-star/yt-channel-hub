<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use YtHub\AdminAuth;
use YtHub\Lang;

/**
 * Entspricht admin/_bootstrap.php (ohne login.php): Session, Sprache, Login-Pflicht.
 */
final class EnsureYtHubAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        AdminAuth::startSession();
        Lang::init();

        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($script === 'login.php') {
            return $next($request);
        }

        if (! AdminAuth::isLoggedIn()) {
            return redirect('/admin/login.php');
        }

        return $next($request);
    }
}

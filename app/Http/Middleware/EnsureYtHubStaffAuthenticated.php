<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use YtHub\Lang;
use YtHub\StaffAuth;

final class EnsureYtHubStaffAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        StaffAuth::startSession();
        Lang::init();

        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($script === 'login.php') {
            return $next($request);
        }

        if (! StaffAuth::isLoggedIn()) {
            return redirect('/staff/login.php');
        }

        return $next($request);
    }
}

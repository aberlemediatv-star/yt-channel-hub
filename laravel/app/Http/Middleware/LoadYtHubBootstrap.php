<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class LoadYtHubBootstrap
{
    public function handle(Request $request, Closure $next): Response
    {
        require_once base_path('src/bootstrap.php');

        return $next($request);
    }
}

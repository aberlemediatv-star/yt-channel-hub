<?php

use App\Http\Middleware\EnsureYtHubAdminAuthenticated;
use App\Http\Middleware\EnsureYtHubStaffAuthenticated;
use App\Http\Middleware\InternalToken;
use App\Http\Middleware\LoadYtHubBootstrap;
use App\Http\Middleware\SetYtHubLegacyScriptName;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require base_path('routes/system.php');
            require base_path('routes/admin.php');
            require base_path('routes/staff.php');
            require base_path('routes/legacy.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        $middleware->alias([
            'internal.token' => InternalToken::class,
            'yt.bootstrap' => LoadYtHubBootstrap::class,
            'yt.script' => SetYtHubLegacyScriptName::class,
            'yt.admin.auth' => EnsureYtHubAdminAuthenticated::class,
            'yt.staff.auth' => EnsureYtHubStaffAuthenticated::class,
        ]);
        $middleware->web(replace: [
            StartSession::class => App\Http\Middleware\StartSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

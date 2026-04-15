<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\Middleware\StartSession as BaseStartSession;

/**
 * Laravel is mounted under /app in nginx; REQUEST_URI seen by PHP omits that prefix.
 * The default session "previous URL" would miss /app, so redirect()->back() after
 * validation errors can send users to the legacy site. We store the public URL instead.
 */
final class StartSession extends BaseStartSession
{
    protected function storeCurrentUrl(Request $request, $session): void
    {
        if (! $request->isMethod('GET') ||
            ! $request->route() instanceof Route ||
            $request->ajax() ||
            $request->prefetch() ||
            $request->isPrecognitive()) {
            return;
        }

        $publicUrl = $this->publicFullUrl($request);

        $session->setPreviousUrl($publicUrl);

        if (method_exists($session, 'setPreviousRoute')) {
            $session->setPreviousRoute($request->route()->getName());
        }
    }

    private function publicFullUrl(Request $request): string
    {
        $base = rtrim((string) config('app.url'), '/');
        if ($base === '' || ! str_ends_with($base, '/app')) {
            return $request->fullUrl();
        }

        $uri = $request->getRequestUri();
        if (str_starts_with($uri, '/app')) {
            return $request->fullUrl();
        }

        return $request->getScheme().'://'.$request->getHttpHost().'/app'.$uri;
    }
}

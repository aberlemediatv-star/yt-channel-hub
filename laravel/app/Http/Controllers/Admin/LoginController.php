<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use YtHub\AdminAuth;
use YtHub\Csrf;
use YtHub\Lang;
use YtHub\PublicHttp;

final class LoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();
        AdminAuth::startSession();
        Lang::init();

        if (AdminAuth::isLoggedIn()) {
            return redirect('/admin/index.php');
        }

        $error = '';
        if ($request->isMethod('post')) {
            if (! Csrf::validate($request->input('csrf_token'))) {
                $error = Lang::t('admin.login_error_csrf');
            } else {
                $pw = (string) $request->input('password', '');
                $ip = (string) $request->ip();
                if (\App\Support\LoginLockout::isLocked('admin_ip', $ip)) {
                    $error = Lang::t('admin.login_error_locked');
                    AuditLog::systemAction('admin.login_locked', 'admin', null, ['ip' => $ip]);
                } elseif (AdminAuth::login($pw)) {
                    \App\Support\LoginLockout::clear('admin_ip', $ip);
                    AuditLog::adminAction('admin.login', 'admin', null, ['user_agent' => substr((string) $request->userAgent(), 0, 256)]);

                    return redirect('/admin/index.php');
                } else {
                    Log::warning('admin_login_failed', [
                        'ip' => $ip,
                        'user_agent' => substr((string) $request->userAgent(), 0, 512),
                    ]);
                    \App\Support\LoginLockout::registerFailure('admin_ip', $ip);
                    AuditLog::adminAction('admin.login_failed', 'admin', null, ['user_agent' => substr((string) $request->userAgent(), 0, 256)]);
                    $error = Lang::t('admin.login_error');
                }
            }
        }

        return view('admin.login', ['error' => $error]);
    }

    public function logout(Request $request): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        if (! $request->isMethod('post')) {
            abort(405);
        }

        if (! Csrf::validate($request->input('csrf_token'))) {
            abort(403);
        }

        AuditLog::adminAction('admin.logout');
        AdminAuth::logout();

        return redirect('/admin/login.php');
    }
}

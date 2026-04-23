<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Support\AuditLog;
use App\Support\LoginLockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use YtHub\Lang;
use YtHub\PublicHttp;
use YtHub\StaffAuth;
use YtHub\StaffCsrf;

final class LoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();
        StaffAuth::startSession();
        Lang::init();

        if (StaffAuth::isLoggedIn()) {
            return redirect('/staff/index.php');
        }

        $error = '';
        if ($request->isMethod('post')) {
            if (! StaffCsrf::validate($request->input('csrf_token'))) {
                $error = Lang::t('staff.login_error_csrf');
            } else {
                $user = trim((string) $request->input('username', ''));
                $pw = (string) $request->input('password', '');
                if ($user === '' || $pw === '') {
                    $error = Lang::t('staff.login_error_empty');
                } elseif (LoginLockout::isLocked('staff_user', $user) || LoginLockout::isLocked('staff_ip', (string) $request->ip())) {
                    $error = Lang::t('staff.login_error_locked');
                    AuditLog::systemAction('staff.login_locked', 'staff', null, ['username' => $user, 'ip' => $request->ip()]);
                } elseif (! StaffAuth::verifyAndLogin($user, $pw)) {
                    Log::warning('staff_login_failed', [
                        'ip' => $request->ip(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 512),
                    ]);
                    LoginLockout::registerFailure('staff_user', $user);
                    LoginLockout::registerFailure('staff_ip', (string) $request->ip());
                    AuditLog::systemAction('staff.login_failed', 'staff', null, ['username' => $user, 'ip' => $request->ip()]);
                    $error = Lang::t('staff.login_error_auth');
                } else {
                    LoginLockout::clear('staff_user', $user);
                    LoginLockout::clear('staff_ip', (string) $request->ip());
                    AuditLog::staffAction('staff.login');

                    return redirect('/staff/index.php');
                }
            }
        }

        return view('staff.login', ['error' => $error]);
    }

    public function logout(Request $request): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();
        StaffAuth::startSession();

        if (! $request->isMethod('post')) {
            abort(405);
        }

        if (! StaffCsrf::validate($request->input('csrf_token'))) {
            abort(403);
        }

        StaffAuth::logout();

        return redirect('/staff/login.php');
    }
}

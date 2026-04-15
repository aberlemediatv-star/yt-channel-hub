<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
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
                } elseif (! StaffAuth::verifyAndLogin($user, $pw)) {
                    Log::warning('staff_login_failed', [
                        'ip' => $request->ip(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 512),
                    ]);
                    $error = Lang::t('staff.login_error_auth');
                } else {
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

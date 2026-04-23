<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Support\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use YtHub\Db;
use YtHub\Lang;
use YtHub\PublicHttp;
use YtHub\StaffAuth;
use YtHub\StaffCsrf;
use YtHub\StaffRepository;

final class AccountController extends Controller
{
    public function show(Request $request): View
    {
        require_once base_path('src/bootstrap.php');
        PublicHttp::sendSecurityHeaders();
        Lang::init();

        $repo = new StaffRepository(Db::pdo());
        $sid = StaffAuth::staffId();
        $staff = $repo->findById($sid);

        return view('staff.account', [
            'mods' => $repo->getMergedModules($sid),
            'staff' => $staff,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');
        PublicHttp::sendSecurityHeaders();
        Lang::init();

        if (! $request->isMethod('post')) {
            abort(405);
        }
        if (! StaffCsrf::validate((string) $request->input('csrf_token', ''))) {
            return back()->with('err', Lang::t('staff.account_err_csrf'));
        }

        $current = (string) $request->input('current_password', '');
        $new = (string) $request->input('new_password', '');
        $newConfirm = (string) $request->input('new_password_confirm', '');

        if ($current === '' || $new === '' || $newConfirm === '') {
            return back()->with('err', Lang::t('staff.account_err_required'));
        }
        if ($new !== $newConfirm) {
            return back()->with('err', Lang::t('staff.account_err_mismatch'));
        }
        if (mb_strlen($new) < 12) {
            return back()->with('err', Lang::t('staff.account_err_short'));
        }

        $repo = new StaffRepository(Db::pdo());
        $sid = StaffAuth::staffId();
        $row = $repo->findById($sid);
        if ($row === null) {
            abort(403);
        }

        $hash = (string) ($row['password_hash'] ?? '');
        if ($hash === '' || ! password_verify($current, $hash)) {
            AuditLog::staffAction('staff.password_change_failed', 'staff', $sid);

            return back()->with('err', Lang::t('staff.account_err_current_wrong'));
        }

        $repo->updatePasswordHash($sid, password_hash($new, PASSWORD_DEFAULT));
        AuditLog::staffAction('staff.password_change', 'staff', $sid);

        return back()->with('ok', Lang::t('staff.account_ok_password_changed'));
    }
}

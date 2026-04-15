<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use YtHub\AdminFlash;
use YtHub\Csrf;
use YtHub\Db;
use YtHub\Lang;
use YtHub\PublicHttp;
use YtHub\StaffRepository;

final class StaffManageController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        Lang::init();

        $pdo = Db::pdo();
        $repo = new StaffRepository($pdo);

        if ($request->isMethod('post')) {
            PublicHttp::sendSecurityHeaders();

            if (! Csrf::validate($request->input('csrf_token'))) {
                AdminFlash::error(Lang::t('admin.staff_flash_csrf'));
            } else {
                $action = (string) $request->input('action', '');
                if ($action === 'create') {
                    $u = trim((string) $request->input('username', ''));
                    $pw = (string) $request->input('password', '');
                    if ($u === '' || $pw === '') {
                        AdminFlash::error(Lang::t('admin.staff_flash_user_pass'));
                    } elseif ($repo->findByUsername($u) !== null) {
                        AdminFlash::error(Lang::t('admin.staff_flash_user_taken'));
                    } else {
                        $hash = password_hash($pw, PASSWORD_DEFAULT);
                        if ($hash === false) {
                            AdminFlash::error(Lang::t('admin.staff_flash_hash'));
                        } else {
                            $repo->create($u, $hash);
                            AdminFlash::success(Lang::t('admin.staff_flash_created'));
                        }
                    }
                } elseif ($action === 'delete') {
                    $id = (int) $request->input('id', 0);
                    if ($id > 0) {
                        $repo->delete($id);
                        AdminFlash::success(Lang::t('admin.staff_flash_deleted'));
                    }
                }
            }

            return redirect('/admin/staff_manage.php');
        }

        PublicHttp::sendSecurityHeaders();

        $staffList = $repo->listAll();

        return view('admin.staff_manage', [
            'staffList' => $staffList,
            'repo' => $repo,
        ]);
    }
}

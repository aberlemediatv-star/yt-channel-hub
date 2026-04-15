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
use YtHub\StaffModule;
use YtHub\StaffRepository;

final class StaffModulesController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        Lang::init();

        $pdo = Db::pdo();
        $repo = new StaffRepository($pdo);

        $id = (int) $request->query('id', 0);
        $staff = $id > 0 ? $repo->findById($id) : null;
        if ($staff === null) {
            abort(404, Lang::t('admin.staff_not_found'));
        }

        $mods = $repo->getMergedModules($id);

        if ($request->isMethod('post')) {
            PublicHttp::sendSecurityHeaders();

            if (! Csrf::validate($request->input('csrf_token'))) {
                AdminFlash::error(Lang::t('admin.staff_flash_csrf'));
            } else {
                $next = [];
                /** @var array<string, mixed> $postMod */
                $postMod = $request->input('mod', []);
                if (! is_array($postMod)) {
                    $postMod = [];
                }
                foreach (StaffModule::allKeys() as $k) {
                    $next[$k] = isset($postMod[$k]);
                }
                $repo->setModules($id, $next);
                AdminFlash::success(Lang::t('admin.staff_modules_saved'));
            }

            return redirect('/admin/staff_modules.php?id='.$id);
        }

        PublicHttp::sendSecurityHeaders();

        return view('admin.staff_modules', [
            'id' => $id,
            'staff' => $staff,
            'mods' => $mods,
        ]);
    }
}

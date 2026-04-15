<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use YtHub\Db;
use YtHub\PublicHttp;
use YtHub\StaffAuth;
use YtHub\StaffRepository;

final class DashboardController extends Controller
{
    public function index(): View
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();

        $pdo = Db::pdo();
        $repo = new StaffRepository($pdo);
        $sid = StaffAuth::staffId();
        $mods = $repo->getMergedModules($sid);
        $channels = $repo->listChannelsForStaff($sid);

        return view('staff.index', [
            'mods' => $mods,
            'channels' => $channels,
        ]);
    }
}

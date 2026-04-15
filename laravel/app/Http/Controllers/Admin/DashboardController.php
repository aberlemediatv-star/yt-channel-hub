<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use YtHub\ChannelRepository;
use YtHub\Db;
use YtHub\JobQueue;
use YtHub\Lang;
use YtHub\PublicHttp;

final class DashboardController extends Controller
{
    public function index(): View
    {
        require_once base_path('src/bootstrap.php');
        Lang::init();

        PublicHttp::sendSecurityHeaders();

        $pdo = Db::pdo();
        $channels = (new ChannelRepository($pdo))->listAllAdmin();
        $jobs = JobQueue::listRecent($pdo, 25);

        $root = base_path();
        $workerNote = sprintf(Lang::t('admin.worker_note'), $root);

        return view('admin.index', [
            'channels' => $channels,
            'jobs' => $jobs,
            'workerNote' => $workerNote,
        ]);
    }
}

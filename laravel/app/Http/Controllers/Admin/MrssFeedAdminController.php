<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use YtHub\ChannelRepository;
use YtHub\Db;
use YtHub\Lang;

final class MrssFeedAdminController extends Controller
{
    public function index(Request $request): View
    {
        Lang::init();
        $pdo = Db::pdo();
        $repo = new ChannelRepository($pdo);
        $channels = $repo->listAllAdmin();

        $appUrl = rtrim((string) config('app.url', ''), '/');

        return view('admin.mrss-feeds', [
            'token' => (string) $request->query('token', ''),
            'channels' => $channels,
            'appUrl' => $appUrl,
        ]);
    }
}

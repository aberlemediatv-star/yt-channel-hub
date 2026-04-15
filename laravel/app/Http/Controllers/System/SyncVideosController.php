<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use YtHub\PublicHttp;
use YtHub\Sync\VideoSyncRunner;

final class SyncVideosController extends Controller
{
    public function show(): Response
    {
        require_once base_path('src/bootstrap.php');

        ob_start();
        PublicHttp::sendSecurityHeaders();
        VideoSyncRunner::run();
        $body = (string) ob_get_clean();

        return response($body, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    }
}

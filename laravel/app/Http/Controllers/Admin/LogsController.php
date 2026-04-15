<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use YtHub\Lang;
use YtHub\PublicHttp;

final class LogsController extends Controller
{
    public function show(): View
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();
        Lang::init();

        $projectRoot = base_path();
        $logsDir = $projectRoot.'/storage/logs';
        $logFile = $logsDir.'/app.log';

        $lines = [];
        $err = '';
        $realLogs = realpath($logsDir);
        $realFile = is_readable($logFile) ? realpath($logFile) : false;
        if ($realFile === false || $realLogs === false || ! str_starts_with($realFile, $realLogs)) {
            $err = Lang::t('admin.logs_unreadable');
        } else {
            $maxBytes = 512 * 1024;
            $size = filesize($realFile);
            if ($size === false) {
                $err = Lang::t('admin.logs_unreadable');
            } else {
                $fp = fopen($realFile, 'rb');
                if ($fp === false) {
                    $err = Lang::t('admin.logs_unreadable');
                } else {
                    if ($size > $maxBytes) {
                        fseek($fp, -$maxBytes, SEEK_END);
                    }
                    $chunk = stream_get_contents($fp);
                    fclose($fp);
                    if ($chunk !== false && $chunk !== '') {
                        $rawLines = preg_split('/\R/', $chunk) ?: [];
                        $lines = array_slice($rawLines, -400);
                    }
                }
            }
        }

        return view('admin.logs', [
            'lines' => $lines,
            'err' => $err,
        ]);
    }
}

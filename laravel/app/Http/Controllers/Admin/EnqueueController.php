<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use YtHub\AdminFlash;
use YtHub\Csrf;
use YtHub\Db;
use YtHub\JobQueue;

final class EnqueueController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        if (! $request->isMethod('post')) {
            abort(405);
        }

        if (! Csrf::validate($request->input('csrf_token'))) {
            AdminFlash::error('Ungültige Anfrage (CSRF). Bitte Seite neu laden.');

            return redirect('/admin/index.php');
        }

        $type = (string) $request->input('job_type', '');
        $channelId = $request->has('channel_id') ? (int) $request->input('channel_id') : null;
        $days = $request->has('days') ? max(1, min(366, (int) $request->input('days'))) : 28;

        $pdo = Db::pdo();
        $payload = null;
        if (str_contains($type, 'analytics')) {
            $payload = ['days' => $days];
        }

        try {
            switch ($type) {
                case JobQueue::TYPE_VIDEO_SYNC_ALL:
                    JobQueue::enqueue($pdo, $type, null, null);
                    break;
                case JobQueue::TYPE_VIDEO_SYNC_CHANNEL:
                    if ($channelId === null || $channelId <= 0) {
                        throw new \InvalidArgumentException('Kanal-ID fehlt');
                    }
                    JobQueue::enqueue($pdo, $type, $channelId, null);
                    break;
                case JobQueue::TYPE_ANALYTICS_SYNC_ALL:
                    JobQueue::enqueue($pdo, $type, null, $payload);
                    break;
                case JobQueue::TYPE_ANALYTICS_SYNC_CHANNEL:
                    if ($channelId === null || $channelId <= 0) {
                        throw new \InvalidArgumentException('Kanal-ID fehlt');
                    }
                    JobQueue::enqueue($pdo, $type, $channelId, $payload);
                    break;
                default:
                    throw new \InvalidArgumentException('Unbekannter Job-Typ');
            }
            AdminFlash::success('Job eingereiht.');
        } catch (\Throwable $e) {
            AdminFlash::error($e->getMessage());
        }

        return redirect('/admin/index.php');
    }
}

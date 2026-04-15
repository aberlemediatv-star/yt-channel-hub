<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use YtHub\BackendUploadHandler;
use YtHub\ChannelRepository;
use YtHub\Db;
use YtHub\HttpDateRange;
use YtHub\HttpGuard;
use YtHub\Lang;
use YtHub\PublicHttp;
use YtHub\TokenCipher;
use YtHub\YouTubeAnalyticsService;

final class BackendController extends Controller
{
    public function handle(Request $request): View|Response|RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        $status = HttpGuard::internalAuthStatusForCookieOrAdmin();
        if ($status === 503) {
            return response(
                "Interner Token nicht konfiguriert (security.internal_token / INTERNAL_TOKEN).\n",
                503,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }
        if ($status === 403) {
            return response(
                "Forbidden — Token, Admin-Login oder Header X-Internal-Token erforderlich.\n",
                403,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        PublicHttp::sendSecurityHeaders();
        Lang::init();

        $pdo = Db::pdo();

        if ($request->isMethod('post') && ($request->input('backend_action') ?? '') === 'upload') {
            $cipher = new TokenCipher(app_config()['security']['encryption_key'] ?? null);
            $result = BackendUploadHandler::process(
                $pdo,
                $cipher,
                (string) ($_SESSION['backend_csrf'] ?? ''),
                (string) ($request->input('backend_csrf') ?? ''),
                static fn (string $k): string => Lang::t($k)
            );
            $_SESSION['backend_csrf'] = bin2hex(random_bytes(32));
            if (! empty($request->input('ajax'))) {
                return response()->json([
                    'ok' => $result['ok'],
                    'message' => $result['message'],
                    'videoId' => $result['videoId'],
                    'csrf' => $_SESSION['backend_csrf'],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }
            $_SESSION['backend_flash'] = [
                'ok' => $result['ok'],
                'message' => $result['message'],
            ];
            $redir = ['lang' => Lang::code()];
            if ($request->filled('range_start')) {
                $redir['start'] = (string) $request->input('range_start');
            }
            if ($request->filled('range_end')) {
                $redir['end'] = (string) $request->input('range_end');
            }

            return redirect()->to(url('/backend.php').'?'.http_build_query($redir).'#upload');
        }

        if (empty($_SESSION['backend_csrf'])) {
            $_SESSION['backend_csrf'] = bin2hex(random_bytes(32));
        }
        $backendCsrf = $_SESSION['backend_csrf'];

        $buFlash = null;
        if (! empty($_SESSION['backend_flash'])) {
            $buFlash = $_SESSION['backend_flash'];
            unset($_SESSION['backend_flash']);
        }

        $range = HttpDateRange::fromGet($request->query->all(), 30);
        $start = $range['start'];
        $end = $range['end'];

        $client = app_google_client();
        $analyticsSvc = new YouTubeAnalyticsService($client, $pdo);
        $totals = $analyticsSvc->aggregateTotals($start, $end);
        $perChannel = $analyticsSvc->perChannelTotals($start, $end);

        $channelRepo = new ChannelRepository($pdo);
        $uploadChannels = $channelRepo->listActiveForBackend();

        $uploadMax = ini_get('upload_max_filesize') ?: '?';
        $postMax = ini_get('post_max_size') ?: '?';

        return response()->view('site.backend', [
            'start' => $start,
            'end' => $end,
            'totals' => $totals,
            'perChannel' => $perChannel,
            'uploadChannels' => $uploadChannels,
            'uploadMax' => $uploadMax,
            'postMax' => $postMax,
            'backendCsrf' => $backendCsrf,
            'buFlash' => $buFlash,
            'hreflangPage' => 'backend.php',
            'idPrefix' => 'bu',
        ]);
    }
}

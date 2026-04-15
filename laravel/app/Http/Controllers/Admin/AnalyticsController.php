<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use YtHub\AnalyticsExportService;
use YtHub\ChannelRepository;
use YtHub\Csrf;
use YtHub\Db;
use YtHub\HttpDateRange;
use YtHub\Lang;
use YtHub\PublicHttp;
use YtHub\YouTubeAnalyticsService;

final class AnalyticsController extends Controller
{
    public function show(Request $request): View
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();
        Lang::init();

        $pdo = Db::pdo();
        $range = HttpDateRange::fromGet($request->query->all(), 30);
        $start = $range['start'];
        $end = $range['end'];

        $channelsAll = (new ChannelRepository($pdo))->listAllAdmin();

        $client = app_google_client();
        $analyticsSvc = new YouTubeAnalyticsService($client, $pdo);
        $totals = $analyticsSvc->aggregateTotals($start, $end);
        $perChannel = $analyticsSvc->perChannelTotals($start, $end);

        return view('admin.analytics', [
            'start' => $start,
            'end' => $end,
            'totals' => $totals,
            'perChannel' => $perChannel,
            'channelsAll' => $channelsAll,
        ]);
    }

    public function export(Request $request): Response
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();
        Lang::init();

        if (! $request->isMethod('post')) {
            return response('POST only', 405, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (! Csrf::validate($request->input('csrf_token'))) {
            return response(Lang::t('admin.export_error_csrf'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $range = HttpDateRange::fromPost($request->all(), 30);
        $start = $range['start'];
        $end = $range['end'];

        $format = (string) ($request->input('format') ?? AnalyticsExportService::FORMAT_EXCEL);
        if (! in_array($format, [
            AnalyticsExportService::FORMAT_EXCEL,
            AnalyticsExportService::FORMAT_SAP,
            AnalyticsExportService::FORMAT_JSON,
        ], true)) {
            $format = AnalyticsExportService::FORMAT_EXCEL;
        }

        $channelId = (int) $request->input('channel_id', 0);
        $channelFilter = $channelId > 0 ? $channelId : null;

        $pdo = Db::pdo();
        $svc = new AnalyticsExportService;
        $rows = $svc->fetchDailyRows($pdo, $start, $end, $channelFilter);

        if ($format === AnalyticsExportService::FORMAT_JSON) {
            $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $fname = 'yt_analytics_'.$start.'_'.$end.'.json';

            return response($json, 200, [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$fname.'"',
                'Cache-Control' => 'no-store',
            ]);
        }

        $csv = $svc->toCsv($format, $rows);

        $suffix = AnalyticsExportService::filenameSuffix($format);
        $fname = 'yt_analytics_'.$start.'_'.$end.'_'.$suffix.'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$fname.'"',
            'Cache-Control' => 'no-store',
        ]);
    }
}

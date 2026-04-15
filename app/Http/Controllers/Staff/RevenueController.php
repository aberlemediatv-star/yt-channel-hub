<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use YtHub\Db;
use YtHub\HttpDateRange;
use YtHub\Lang;
use YtHub\PublicHttp;
use YtHub\StaffAuth;
use YtHub\StaffModule;
use YtHub\StaffRepository;

final class RevenueController extends Controller
{
    public function index(Request $request): View|Response
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();
        Lang::init();

        $pdo = Db::pdo();
        $repo = new StaffRepository($pdo);
        $sid = StaffAuth::staffId();

        if (! $repo->hasModule($sid, StaffModule::VIEW_REVENUE)) {
            return response(Lang::t('staff.module_denied'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $mods = $repo->getMergedModules($sid);
        $ids = $repo->allowedChannelIds($sid);
        $range = HttpDateRange::fromGet($request->query->all(), 30);
        $start = $range['start'];
        $end = $range['end'];

        $rows = [];
        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT c.id, c.title, c.sort_order,
            SUM(COALESCE(a.estimated_revenue, 0)) AS rev,
            SUM(COALESCE(a.estimated_ad_revenue, 0)) AS adrev,
            SUM(COALESCE(a.views, 0)) AS views
            FROM channels c
            LEFT JOIN analytics_daily a ON a.channel_id = c.id AND a.report_date BETWEEN ? AND ?
            WHERE c.id IN ($placeholders)
            GROUP BY c.id, c.title, c.sort_order
            ORDER BY c.sort_order ASC, c.id ASC";
            $st = $pdo->prepare($sql);
            $params = array_merge([$start, $end], $ids);
            $st->execute($params);
            $rows = $st->fetchAll();
        }

        return view('staff.revenue', [
            'mods' => $mods,
            'ids' => $ids,
            'start' => $start,
            'end' => $end,
            'rows' => $rows,
        ]);
    }
}

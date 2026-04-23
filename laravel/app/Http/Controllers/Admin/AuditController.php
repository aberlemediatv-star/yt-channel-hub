<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use YtHub\Lang;
use YtHub\PublicHttp;

final class AuditController extends Controller
{
    public function show(Request $request): View
    {
        require_once base_path('src/bootstrap.php');
        PublicHttp::sendSecurityHeaders();
        Lang::init();

        $filters = [
            'actor_type' => (string) $request->query('actor_type', ''),
            'action' => (string) $request->query('action', ''),
            'target_type' => (string) $request->query('target_type', ''),
        ];

        $q = DB::table('admin_audit_log');
        foreach (['actor_type', 'action', 'target_type'] as $k) {
            if ($filters[$k] !== '') {
                $q->where($k, $filters[$k]);
            }
        }

        $rows = $q->orderByDesc('id')->limit(500)->get();

        // Distinct values for the filter dropdowns (cheap on a small table).
        $actorTypes = DB::table('admin_audit_log')->select('actor_type')->distinct()->pluck('actor_type')->filter()->values();
        $actions = DB::table('admin_audit_log')->select('action')->distinct()->orderBy('action')->pluck('action')->filter()->values();
        $targetTypes = DB::table('admin_audit_log')->select('target_type')->distinct()->orderBy('target_type')->pluck('target_type')->filter()->values();

        return view('admin.audit', [
            'rows' => $rows,
            'filters' => $filters,
            'actorTypes' => $actorTypes,
            'actions' => $actions,
            'targetTypes' => $targetTypes,
        ]);
    }
}

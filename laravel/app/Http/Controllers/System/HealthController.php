<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use YtHub\Db;
use YtHub\PublicHttp;

final class HealthController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        PublicHttp::sendSecurityHeaders();

        $token = (string) ($request->query('token') ?? '');
        if ($token === '') {
            return response()->json([
                'ok' => true,
                'scope' => 'liveness',
            ], 200, [], JSON_THROW_ON_ERROR);
        }

        require_once base_path('src/bootstrap.php');

        $cfg = app_config();
        $internal = (string) ($cfg['security']['internal_token'] ?? '');
        $full = $internal !== '' && hash_equals($internal, $token);

        if (! $full) {
            return response()->json([
                'ok' => true,
                'scope' => 'liveness',
            ], 200, [], JSON_THROW_ON_ERROR);
        }

        $checks = [
            'php' => PHP_VERSION,
            'database' => false,
            'logs_writable' => false,
        ];

        try {
            Db::pdo()->query('SELECT 1');
            $checks['database'] = true;
        } catch (\Throwable $e) {
            $checks['database_error'] = $e->getMessage();
        }

        $logDir = storage_path('logs');
        $checks['logs_writable'] = is_dir($logDir) && is_writable($logDir);
        if (! is_dir($logDir)) {
            $checks['logs_writable'] = @mkdir($logDir, 0755, true) && is_writable($logDir);
        }

        $ok = $checks['database'] === true;

        return response()->json(['ok' => $ok, 'checks' => $checks], $ok ? 200 : 503, [], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\CloudImport\DropboxCloudService;
use App\Services\CloudImport\GdriveCloudService;
use App\Services\CloudImport\S3VideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use YtHub\Db;
use YtHub\Lang;
use YtHub\StaffAuth;
use YtHub\StaffModule;
use YtHub\StaffRepository;

final class CloudFilesController extends Controller
{
    public function index(Request $request, GdriveCloudService $gdrive, DropboxCloudService $dropbox, S3VideoService $s3): JsonResponse|Response
    {
        require_once base_path('src/bootstrap.php');
        Lang::init();

        $pdo = Db::pdo();
        $staffRepo = new StaffRepository($pdo);
        $sid = StaffAuth::staffId();
        $channelId = (int) $request->query('channel_id', 0);
        if ($channelId <= 0 || ! $staffRepo->staffMayAccessChannel($sid, $channelId)) {
            return response(Lang::t('staff.upload_error_channel_access'), 403);
        }
        if (! $staffRepo->hasModule($sid, StaffModule::UPLOAD)) {
            return response(Lang::t('staff.module_denied'), 403);
        }

        $provider = (string) $request->query('provider', '');
        try {
            return match ($provider) {
                'gdrive' => response()->json(
                    $gdrive->isConnected()
                        ? $gdrive->browse((string) $request->query('folder', 'root'))
                        : ['folder_id' => 'root', 'parent_id' => null, 'entries' => []]
                ),
                'dropbox' => response()->json(
                    $dropbox->isConnected()
                        ? $dropbox->browse((string) $request->query('path', ''))
                        : ['path' => '', 'can_up' => false, 'parent_path' => null, 'entries' => []]
                ),
                's3' => response()->json(['files' => $s3->isConfigured() ? $s3->listVideoObjects() : []]),
                default => response()->json(['error' => 'bad_provider'], 400),
            };
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('staff_cloudfiles_failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'cloud_request_failed'], 502);
        }
    }
}

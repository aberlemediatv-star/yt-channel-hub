<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\CloudImport\DropboxCloudService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use YtHub\Lang;
use YtHub\StaffCsrf;

final class DropboxOAuthController extends Controller
{
    public function start(Request $request, DropboxCloudService $dropbox): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');
        Lang::init();

        if (! $dropbox->isOAuthConfigured()) {
            return redirect()->to('/staff/upload.php?channel_id='.(int) $request->query('channel_id', 0))
                ->with('cloud_error', Lang::t('staff.cloud_dropbox_not_configured'));
        }
        $state = Str::random(40);
        $request->session()->put('cloud_oauth_dropbox_state', $state);
        $request->session()->put('cloud_oauth_return_channel_id', (int) $request->query('channel_id', 0));

        return redirect()->away($dropbox->buildAuthUrl($state));
    }

    public function callback(Request $request, DropboxCloudService $dropbox): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');
        Lang::init();

        $channelId = (int) $request->session()->pull('cloud_oauth_return_channel_id', 0);
        $expected = (string) $request->session()->pull('cloud_oauth_dropbox_state', '');
        $state = (string) $request->query('state', '');
        if ($expected === '' || $state === '' || ! hash_equals($expected, $state)) {
            return redirect()->to('/staff/upload.php?channel_id='.$channelId)
                ->with('cloud_error', Lang::t('staff.cloud_oauth_state_invalid'));
        }
        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->to('/staff/upload.php?channel_id='.$channelId)
                ->with('cloud_error', Lang::t('staff.cloud_oauth_denied'));
        }
        try {
            $dropbox->exchangeCodeForRefreshToken($code);
        } catch (\Throwable $e) {
            Log::warning('dropbox_oauth_exchange_failed', ['error' => $e->getMessage()]);

            return redirect()->to('/staff/upload.php?channel_id='.$channelId)
                ->with('cloud_error', Lang::t('staff.cloud_oauth_denied'));
        }

        return redirect()->to('/staff/upload.php?channel_id='.$channelId)
            ->with('cloud_ok', Lang::t('staff.cloud_dropbox_connected'));
    }

    public function disconnect(Request $request, DropboxCloudService $dropbox): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');
        Lang::init();

        if (! $request->isMethod('post')) {
            abort(405);
        }
        if (! StaffCsrf::validate((string) $request->input('csrf_token', ''))) {
            abort(403);
        }

        $channelId = (int) $request->input('channel_id', 0);
        $dropbox->disconnect();

        return redirect()->to('/staff/upload.php?channel_id='.$channelId)
            ->with('cloud_ok', Lang::t('staff.cloud_dropbox_disconnected'));
    }
}

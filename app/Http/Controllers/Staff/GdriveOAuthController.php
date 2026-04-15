<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\CloudImport\GdriveCloudService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use YtHub\Lang;

final class GdriveOAuthController extends Controller
{
    public function start(Request $request, GdriveCloudService $gdrive): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');
        Lang::init();

        if (! $gdrive->isOAuthConfigured()) {
            return redirect()->to('/staff/upload.php?channel_id='.(int) $request->query('channel_id', 0))
                ->with('cloud_error', Lang::t('staff.cloud_gdrive_not_configured'));
        }
        $state = Str::random(40);
        $request->session()->put('cloud_oauth_gdrive_state', $state);
        $request->session()->put('cloud_oauth_return_channel_id', (int) $request->query('channel_id', 0));

        return redirect()->away($gdrive->buildAuthUrl($state));
    }

    public function callback(Request $request, GdriveCloudService $gdrive): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');
        Lang::init();

        $channelId = (int) $request->session()->pull('cloud_oauth_return_channel_id', 0);
        $expected = (string) $request->session()->pull('cloud_oauth_gdrive_state', '');
        $state = (string) $request->query('state', '');
        if ($expected === '' || $state !== $expected) {
            return redirect()->to('/staff/upload.php?channel_id='.$channelId)
                ->with('cloud_error', Lang::t('staff.cloud_oauth_state_invalid'));
        }
        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->to('/staff/upload.php?channel_id='.$channelId)
                ->with('cloud_error', Lang::t('staff.cloud_oauth_denied'));
        }
        try {
            $gdrive->exchangeCodeForRefreshToken($code);
        } catch (\Throwable $e) {
            return redirect()->to('/staff/upload.php?channel_id='.$channelId)
                ->with('cloud_error', $e->getMessage());
        }

        return redirect()->to('/staff/upload.php?channel_id='.$channelId)
            ->with('cloud_ok', Lang::t('staff.cloud_gdrive_connected'));
    }

    public function disconnect(Request $request, GdriveCloudService $gdrive): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');
        Lang::init();
        $channelId = (int) $request->query('channel_id', 0);
        $gdrive->disconnect();

        return redirect()->to('/staff/upload.php?channel_id='.$channelId)
            ->with('cloud_ok', Lang::t('staff.cloud_gdrive_disconnected'));
    }
}

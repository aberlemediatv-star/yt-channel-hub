<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use YtHub\Lang;

final class SocialSettingsController extends Controller
{
    public function edit(): View
    {
        Lang::init();

        return view('admin.social.settings', [
            'metaAppId' => SocialSetting::getDecrypted('meta.app_id', ''),
            'metaAppSecret' => SocialSetting::getDecrypted('meta.app_secret', ''),
            'xClientId' => SocialSetting::getDecrypted('x.client_id', ''),
            'xClientSecret' => SocialSetting::getDecrypted('x.client_secret', ''),
            'tiktokClientKey' => SocialSetting::getDecrypted('tiktok.client_key', ''),
            'tiktokClientSecret' => SocialSetting::getDecrypted('tiktok.client_secret', ''),
            'facebookEnabled' => SocialSetting::getDecrypted('facebook.enabled', '0') === '1',
            'facebookAppId' => SocialSetting::getDecrypted('facebook.app_id', ''),
            'facebookAppSecret' => SocialSetting::getDecrypted('facebook.app_secret', ''),
            'linkedinEnabled' => SocialSetting::getDecrypted('linkedin.enabled', '0') === '1',
            'linkedinClientId' => SocialSetting::getDecrypted('linkedin.client_id', ''),
            'linkedinClientSecret' => SocialSetting::getDecrypted('linkedin.client_secret', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Laravel lives behind /app in nginx; force URL generation to include it.
        URL::forceRootUrl((string) config('app.url', 'http://localhost'));

        $data = $request->validate([
            'meta_app_id' => ['nullable', 'string', 'max:128'],
            'meta_app_secret' => ['nullable', 'string', 'max:255'],
            'x_client_id' => ['nullable', 'string', 'max:128'],
            'x_client_secret' => ['nullable', 'string', 'max:255'],
            'tiktok_client_key' => ['nullable', 'string', 'max:128'],
            'tiktok_client_secret' => ['nullable', 'string', 'max:255'],
            'facebook_enabled' => ['nullable', 'in:1'],
            'facebook_app_id' => ['nullable', 'string', 'max:128'],
            'facebook_app_secret' => ['nullable', 'string', 'max:255'],
            'linkedin_enabled' => ['nullable', 'in:1'],
            'linkedin_client_id' => ['nullable', 'string', 'max:128'],
            'linkedin_client_secret' => ['nullable', 'string', 'max:255'],
        ]);

        SocialSetting::setEncrypted('meta.app_id', (string) ($data['meta_app_id'] ?? ''));
        SocialSetting::setEncrypted('meta.app_secret', (string) ($data['meta_app_secret'] ?? ''));
        SocialSetting::setEncrypted('x.client_id', (string) ($data['x_client_id'] ?? ''));
        SocialSetting::setEncrypted('x.client_secret', (string) ($data['x_client_secret'] ?? ''));
        SocialSetting::setEncrypted('tiktok.client_key', (string) ($data['tiktok_client_key'] ?? ''));
        SocialSetting::setEncrypted('tiktok.client_secret', (string) ($data['tiktok_client_secret'] ?? ''));
        SocialSetting::setEncrypted('facebook.enabled', isset($data['facebook_enabled']) ? '1' : '0');
        SocialSetting::setEncrypted('facebook.app_id', (string) ($data['facebook_app_id'] ?? ''));
        SocialSetting::setEncrypted('facebook.app_secret', (string) ($data['facebook_app_secret'] ?? ''));
        SocialSetting::setEncrypted('linkedin.enabled', isset($data['linkedin_enabled']) ? '1' : '0');
        SocialSetting::setEncrypted('linkedin.client_id', (string) ($data['linkedin_client_id'] ?? ''));
        SocialSetting::setEncrypted('linkedin.client_secret', (string) ($data['linkedin_client_secret'] ?? ''));

        return redirect()
            ->to('/admin/social/settings?token='.urlencode((string) $request->query('token', '')))
            ->with('status', 'Gespeichert.');
    }
}

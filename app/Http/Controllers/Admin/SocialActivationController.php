<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use YtHub\Lang;

final class SocialActivationController extends Controller
{
    public function index(Request $request): View
    {
        Lang::init();
        $token = (string) $request->query('token', '');
        $appUrl = rtrim((string) config('app.url'), '/');

        $metaOk = SocialSetting::getDecrypted('meta.app_id', '') !== '' && SocialSetting::getDecrypted('meta.app_secret', '') !== '';
        $xOk = SocialSetting::getDecrypted('x.client_id', '') !== '' && SocialSetting::getDecrypted('x.client_secret', '') !== '';
        $tiktokOk = SocialSetting::getDecrypted('tiktok.client_key', '') !== '' && SocialSetting::getDecrypted('tiktok.client_secret', '') !== '';
        $facebookEnabled = SocialSetting::getDecrypted('facebook.enabled', '0') === '1';
        $facebookOk = SocialSetting::getDecrypted('facebook.app_id', '') !== '' && SocialSetting::getDecrypted('facebook.app_secret', '') !== '';
        $linkedinEnabled = SocialSetting::getDecrypted('linkedin.enabled', '0') === '1';
        $linkedinOk = SocialSetting::getDecrypted('linkedin.client_id', '') !== '' && SocialSetting::getDecrypted('linkedin.client_secret', '') !== '';

        return view('admin.social.activate', [
            'token' => $token,
            'appUrl' => $appUrl,
            'checks' => [
                'app_url_set' => $appUrl !== '',
                'app_url_https' => $appUrl !== '' && str_starts_with($appUrl, 'https://'),
                'meta_keys' => $metaOk,
                'x_keys' => $xOk,
                'tiktok_keys' => $tiktokOk,
                'facebook_enabled' => $facebookEnabled,
                'facebook_keys' => $facebookOk,
                'facebook_incomplete' => $facebookEnabled && ! $facebookOk,
                'linkedin_enabled' => $linkedinEnabled,
                'linkedin_keys' => $linkedinOk,
                'linkedin_incomplete' => $linkedinEnabled && ! $linkedinOk,
            ],
            'redirects' => [
                'x' => $appUrl !== '' ? ($appUrl.'/oauth/x/callback') : '',
                'tiktok' => $appUrl !== '' ? ($appUrl.'/oauth/tiktok/callback') : '',
                'meta' => $appUrl !== '' ? ($appUrl.'/oauth/meta/callback') : '',
                'facebook' => $appUrl !== '' ? ($appUrl.'/oauth/facebook/callback') : '',
                'linkedin' => $appUrl !== '' ? ($appUrl.'/oauth/linkedin/callback') : '',
            ],
        ]);
    }
}

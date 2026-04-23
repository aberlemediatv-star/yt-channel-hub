<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\SocialSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class SocialOAuthTikTokController
{
    public function start(Request $request): RedirectResponse
    {
        $clientKey = SocialSetting::getDecrypted('tiktok.client_key', '');
        if ($clientKey === '') {
            abort(400, 'TikTok Client Key fehlt (Settings).');
        }

        $state = Str::random(32);

        $request->session()->put('tiktok_oauth_state', $state);

        $redirectUri = rtrim((string) config('app.url', 'http://localhost'), '/').'/oauth/tiktok/callback';

        // Add posting scopes later (video.upload/video.publish) once app is approved.
        $scope = implode(',', [
            'user.info.basic',
        ]);

        $url = 'https://www.tiktok.com/v2/auth/authorize/?'.http_build_query([
            'client_key' => $clientKey,
            'response_type' => 'code',
            'scope' => $scope,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'disable_auto_auth' => 1,
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('tiktok_oauth_state', '');

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');
        $error = (string) $request->query('error', '');

        if ($error !== '') {
            return redirect()->to('/admin/social/accounts');
        }
        if ($expectedState === '' || $state === '' || ! hash_equals($expectedState, $state)) {
            abort(400, 'Ungültiger state.');
        }
        if ($code === '') {
            abort(400, 'OAuth code fehlt.');
        }

        $clientKey = SocialSetting::getDecrypted('tiktok.client_key', '');
        $clientSecret = SocialSetting::getDecrypted('tiktok.client_secret', '');
        if ($clientKey === '' || $clientSecret === '') {
            abort(400, 'TikTok Client Secret fehlt (Settings).');
        }

        $redirectUri = rtrim((string) config('app.url', 'http://localhost'), '/').'/oauth/tiktok/callback';

        // Token endpoint documented under "Manage User Access Tokens".
        $resp = Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key' => $clientKey,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (! $resp->ok()) {
            Log::warning('tiktok_oauth_token_exchange_failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            abort(400, 'TikTok token exchange fehlgeschlagen.');
        }

        $data = $resp->json();
        $accessToken = (string) ($data['access_token'] ?? '');
        $refreshToken = (string) ($data['refresh_token'] ?? '');
        $expiresIn = (int) ($data['expires_in'] ?? 0);
        $scope = (string) ($data['scope'] ?? '');
        $openId = (string) ($data['open_id'] ?? '');

        if ($accessToken === '') {
            abort(400, 'TikTok access_token fehlt.');
        }

        $metaSafe = is_array($data) ? $data : [];
        foreach (['access_token', 'refresh_token', 'id_token'] as $secretKey) {
            unset($metaSafe[$secretKey]);
        }

        $upsertAttrs = [
            'label' => $openId !== '' ? ('TikTok '.$openId) : 'TikTok',
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken !== '' ? $refreshToken : null,
            'token_expires_at' => $expiresIn > 0 ? now()->addSeconds($expiresIn) : null,
            'scopes' => $scope,
            'meta' => [
                'token' => $metaSafe,
            ],
        ];
        if ($openId !== '') {
            SocialAccount::query()->updateOrCreate(
                ['platform' => 'tiktok', 'external_user_id' => $openId],
                $upsertAttrs,
            );
        } else {
            SocialAccount::query()->create(array_merge(
                ['platform' => 'tiktok', 'external_user_id' => null],
                $upsertAttrs,
            ));
        }

        return redirect()->to('/admin/social/accounts');
    }
}

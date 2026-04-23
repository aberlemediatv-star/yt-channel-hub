<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\SocialSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * LinkedIn OAuth 2.0 (OIDC flavour) — scopes:
 *   openid profile email   → for identity
 *   w_member_social        → required to post UGC shares
 *
 * Works with "Sign In with LinkedIn using OpenID Connect" product + "Share
 * on LinkedIn" product on the app.
 */
final class SocialOAuthLinkedInController
{
    private const AUTHORIZE = 'https://www.linkedin.com/oauth/v2/authorization';
    private const TOKEN = 'https://www.linkedin.com/oauth/v2/accessToken';
    private const API = 'https://api.linkedin.com/v2';

    public function start(Request $request): RedirectResponse
    {
        $clientId = SocialSetting::getDecrypted('linkedin.client_id', '');
        if ($clientId === '') {
            abort(400, 'LinkedIn Client-ID fehlt (Settings).');
        }

        $state = Str::random(40);
        $request->session()->put('linkedin_oauth_state', $state);

        $redirectUri = rtrim((string) config('app.url', 'http://localhost'), '/').'/oauth/linkedin/callback';
        $scopes = 'openid profile email w_member_social';

        $url = self::AUTHORIZE.'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => $scopes,
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $expected = (string) $request->session()->pull('linkedin_oauth_state', '');
        $state = (string) $request->query('state', '');
        if ($expected === '' || $state === '' || ! hash_equals($expected, $state)) {
            abort(400, 'Ungültiger state.');
        }
        if (($error = (string) $request->query('error', '')) !== '') {
            Log::warning('linkedin_oauth_user_denied', ['error' => $error, 'description' => $request->query('error_description')]);

            return redirect()->to('/admin/social/accounts');
        }
        $code = (string) $request->query('code', '');
        if ($code === '') {
            abort(400, 'OAuth code fehlt.');
        }

        $clientId = SocialSetting::getDecrypted('linkedin.client_id', '');
        $clientSecret = SocialSetting::getDecrypted('linkedin.client_secret', '');
        if ($clientId === '' || $clientSecret === '') {
            abort(400, 'LinkedIn Client-ID / Secret fehlt.');
        }
        $redirectUri = rtrim((string) config('app.url', 'http://localhost'), '/').'/oauth/linkedin/callback';

        $tokenResp = Http::asForm()->acceptJson()->post(self::TOKEN, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);
        if (! $tokenResp->ok()) {
            Log::warning('linkedin_oauth_token_exchange_failed', ['status' => $tokenResp->status(), 'body' => $tokenResp->body()]);
            abort(400, 'LinkedIn token exchange fehlgeschlagen.');
        }
        $data = $tokenResp->json();
        $accessToken = (string) ($data['access_token'] ?? '');
        if ($accessToken === '') {
            abort(400, 'LinkedIn access_token fehlt.');
        }
        $expiresIn = (int) ($data['expires_in'] ?? 0);
        $scope = (string) ($data['scope'] ?? '');

        // OIDC /userinfo gives `sub` which is the urn:li:person:{id}
        $me = Http::withToken($accessToken)->acceptJson()->get(self::API.'/userinfo');
        $sub = (string) ($me->json('sub') ?? '');
        $name = (string) ($me->json('name') ?? 'LinkedIn');
        if ($sub === '') {
            Log::warning('linkedin_userinfo_failed', ['status' => $me->status(), 'body' => $me->body()]);
            abort(400, 'LinkedIn userinfo fehlgeschlagen.');
        }

        // Strip token-ish fields before persisting meta.
        $metaSafe = is_array($data) ? $data : [];
        foreach (['access_token', 'refresh_token', 'id_token'] as $k) {
            unset($metaSafe[$k]);
        }
        $metaSafe['userinfo'] = ['sub' => $sub, 'name' => $name, 'email' => (string) ($me->json('email') ?? '')];

        SocialAccount::query()->updateOrCreate(
            ['platform' => 'linkedin', 'external_user_id' => $sub],
            [
                'label' => 'LI '.$name,
                'access_token' => $accessToken,
                'refresh_token' => (string) ($data['refresh_token'] ?? '') !== '' ? (string) $data['refresh_token'] : null,
                'token_expires_at' => $expiresIn > 0 ? now()->addSeconds($expiresIn) : null,
                'scopes' => $scope,
                'meta' => $metaSafe,
            ],
        );

        return redirect()->to('/admin/social/accounts');
    }
}

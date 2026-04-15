<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\SocialSetting;
use App\Support\Social\Base64Url;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class SocialOAuthXController
{
    public function start(Request $request): RedirectResponse
    {
        $clientId = SocialSetting::getDecrypted('x.client_id', '');
        if ($clientId === '') {
            abort(400, 'X Client ID fehlt (Settings).');
        }

        $token = (string) $request->query('token', '');
        $state = Str::random(32);
        $verifier = Base64Url::encode(random_bytes(32));
        $challenge = Base64Url::encode(hash('sha256', $verifier, true));

        $request->session()->put('x_oauth_state', $state);
        $request->session()->put('x_oauth_token', $token);
        $request->session()->put('x_pkce_verifier', $verifier);

        $redirectUri = rtrim((string) config('app.url', 'http://localhost'), '/').'/oauth/x/callback';

        $scopes = implode(' ', [
            'tweet.read',
            'tweet.write',
            'users.read',
            'offline.access',
            'media.write',
        ]);

        $url = 'https://twitter.com/i/oauth2/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('x_oauth_state', '');
        $token = (string) $request->session()->pull('x_oauth_token', '');
        $verifier = (string) $request->session()->pull('x_pkce_verifier', '');

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');
        $error = (string) $request->query('error', '');

        if ($error !== '') {
            return redirect()->to('/admin/social/accounts?token='.urlencode($token));
        }
        if ($expectedState === '' || $state === '' || ! hash_equals($expectedState, $state)) {
            abort(400, 'Ungültiger state.');
        }
        if ($code === '' || $verifier === '') {
            abort(400, 'OAuth code/verifier fehlt.');
        }

        $clientId = SocialSetting::getDecrypted('x.client_id', '');
        $clientSecret = SocialSetting::getDecrypted('x.client_secret', '');
        if ($clientId === '' || $clientSecret === '') {
            abort(400, 'X Client Secret fehlt (Settings).');
        }

        $redirectUri = rtrim((string) config('app.url', 'http://localhost'), '/').'/oauth/x/callback';

        $resp = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post('https://api.x.com/2/oauth2/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'code_verifier' => $verifier,
            ]);

        if (! $resp->ok()) {
            abort(400, 'X token exchange fehlgeschlagen: '.$resp->body());
        }

        $data = $resp->json();
        $accessToken = (string) ($data['access_token'] ?? '');
        $refreshToken = (string) ($data['refresh_token'] ?? '');
        $expiresIn = (int) ($data['expires_in'] ?? 0);
        $scope = (string) ($data['scope'] ?? '');

        if ($accessToken === '') {
            abort(400, 'X access_token fehlt.');
        }

        // Get user info
        $me = Http::withToken($accessToken)->get('https://api.x.com/2/users/me', [
            'user.fields' => 'id,name,username',
        ]);

        $externalUserId = null;
        $label = 'X';
        if ($me->ok()) {
            $j = $me->json();
            $externalUserId = (string) ($j['data']['id'] ?? '') ?: null;
            $u = (string) ($j['data']['username'] ?? '');
            $label = $u !== '' ? ('X @'.$u) : 'X';
        }

        $upsertAttrs = [
            'label' => $label,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken !== '' ? $refreshToken : null,
            'token_expires_at' => $expiresIn > 0 ? now()->addSeconds($expiresIn) : null,
            'scopes' => $scope,
            'meta' => [
                'me' => $me->ok() ? $me->json() : null,
            ],
        ];
        if ($externalUserId !== null) {
            SocialAccount::query()->updateOrCreate(
                ['platform' => 'x', 'external_user_id' => $externalUserId],
                $upsertAttrs,
            );
        } else {
            SocialAccount::query()->create(array_merge(
                ['platform' => 'x', 'external_user_id' => null],
                $upsertAttrs,
            ));
        }

        return redirect()->to('/admin/social/accounts?token='.urlencode($token));
    }
}

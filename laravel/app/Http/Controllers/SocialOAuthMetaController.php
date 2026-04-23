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
 * Meta / Instagram Login using the "Instagram with Facebook Login" model.
 * Uses a separate app configured under `meta.*` settings so an operator can
 * keep an Instagram-focused app distinct from the Facebook-Pages app.
 *
 * Same Graph endpoints, different app credentials and a narrower scope set
 * aimed at IG publishing only.
 */
final class SocialOAuthMetaController
{
    private const GRAPH = 'https://graph.facebook.com/v19.0';
    private const OAUTH_DIALOG = 'https://www.facebook.com/v19.0/dialog/oauth';

    public function start(Request $request): RedirectResponse
    {
        $appId = SocialSetting::getDecrypted('meta.app_id', '');
        if ($appId === '') {
            abort(400, 'Meta App-ID fehlt (Settings).');
        }

        $state = Str::random(40);
        $request->session()->put('meta_oauth_state', $state);

        $redirectUri = rtrim((string) config('app.url', 'http://localhost'), '/').'/oauth/meta/callback';

        $scopes = implode(',', [
            'public_profile',
            'pages_show_list',
            'instagram_basic',
            'instagram_content_publish',
        ]);

        $url = self::OAUTH_DIALOG.'?'.http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
            'scope' => $scopes,
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $expected = (string) $request->session()->pull('meta_oauth_state', '');
        $state = (string) $request->query('state', '');
        if ($expected === '' || $state === '' || ! hash_equals($expected, $state)) {
            abort(400, 'Ungültiger state.');
        }
        if (($error = (string) $request->query('error', '')) !== '') {
            Log::warning('meta_oauth_user_denied', ['error' => $error]);

            return redirect()->to('/admin/social/accounts');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            abort(400, 'OAuth code fehlt.');
        }

        $appId = SocialSetting::getDecrypted('meta.app_id', '');
        $appSecret = SocialSetting::getDecrypted('meta.app_secret', '');
        if ($appId === '' || $appSecret === '') {
            abort(400, 'Meta App-ID / Secret fehlt.');
        }

        $redirectUri = rtrim((string) config('app.url', 'http://localhost'), '/').'/oauth/meta/callback';

        $tokenResp = Http::acceptJson()->get(self::GRAPH.'/oauth/access_token', [
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);
        if (! $tokenResp->ok()) {
            Log::warning('meta_oauth_code_exchange_failed', ['status' => $tokenResp->status(), 'body' => $tokenResp->body()]);
            abort(400, 'Meta token exchange fehlgeschlagen.');
        }
        $shortToken = (string) ($tokenResp->json('access_token') ?? '');
        if ($shortToken === '') {
            abort(400, 'Meta access_token fehlt.');
        }

        $longResp = Http::acceptJson()->get(self::GRAPH.'/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortToken,
        ]);
        if ($longResp->ok()) {
            $userToken = (string) ($longResp->json('access_token') ?? $shortToken);
            $expiresIn = (int) ($longResp->json('expires_in') ?? 0);
            $userExpiresAt = $expiresIn > 0 ? now()->addSeconds($expiresIn) : null;
        } else {
            Log::warning('meta_oauth_long_exchange_failed', ['status' => $longResp->status(), 'body' => $longResp->body()]);
            $userToken = $shortToken;
            $userExpiresAt = null;
        }

        $pages = Http::withToken($userToken)->acceptJson()->get(self::GRAPH.'/me/accounts', [
            'fields' => 'id,name,access_token,instagram_business_account{id,username,name}',
            'limit' => 100,
        ]);
        $saved = 0;
        if ($pages->ok()) {
            foreach ((array) $pages->json('data', []) as $page) {
                $pageToken = (string) ($page['access_token'] ?? '');
                $igId = (string) ($page['instagram_business_account']['id'] ?? '');
                if ($pageToken === '' || $igId === '') {
                    continue;
                }
                $igName = (string) ($page['instagram_business_account']['username'] ?? $page['instagram_business_account']['name'] ?? '');
                SocialAccount::query()->updateOrCreate(
                    ['platform' => 'instagram', 'external_user_id' => $igId],
                    [
                        'label' => 'IG @'.$igName,
                        'access_token' => $pageToken,
                        'refresh_token' => null,
                        'token_expires_at' => $userExpiresAt,
                        'scopes' => 'instagram_content_publish,instagram_basic',
                        'meta' => [
                            'facebook_page_id' => (string) ($page['id'] ?? ''),
                            'username' => $igName,
                        ],
                    ],
                );
                $saved++;
            }
        } else {
            Log::warning('meta_pages_fetch_failed', ['status' => $pages->status(), 'body' => $pages->body()]);
        }

        if ($saved === 0) {
            Log::warning('meta_oauth_no_ig_business_account', ['graph_status' => $pages->status()]);
        }

        return redirect()->to('/admin/social/accounts');
    }
}

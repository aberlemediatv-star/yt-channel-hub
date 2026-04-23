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
 * Facebook Login (v19+) — also delivers Page access tokens required for
 * Instagram Graph publishing. Scopes: pages_show_list, pages_read_engagement,
 * pages_manage_posts, instagram_basic, instagram_content_publish.
 *
 * After callback we exchange the short-lived user token for a long-lived one,
 * then fetch the user's pages and persist each Page as its own SocialAccount
 * (platform='facebook' plus optional 'instagram' rows for connected IG
 * Business accounts).
 */
final class SocialOAuthFacebookController
{
    private const GRAPH = 'https://graph.facebook.com/v19.0';
    private const OAUTH_DIALOG = 'https://www.facebook.com/v19.0/dialog/oauth';

    public function start(Request $request): RedirectResponse
    {
        $appId = SocialSetting::getDecrypted('facebook.app_id', '');
        if ($appId === '') {
            abort(400, 'Facebook App-ID fehlt (Settings).');
        }

        $state = Str::random(40);
        $request->session()->put('facebook_oauth_state', $state);

        $redirectUri = rtrim((string) config('app.url', 'http://localhost'), '/').'/oauth/facebook/callback';

        $scopes = implode(',', [
            'public_profile',
            'pages_show_list',
            'pages_read_engagement',
            'pages_manage_posts',
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
        $expected = (string) $request->session()->pull('facebook_oauth_state', '');
        $state = (string) $request->query('state', '');
        if ($expected === '' || $state === '' || ! hash_equals($expected, $state)) {
            abort(400, 'Ungültiger state.');
        }
        if (($error = (string) $request->query('error', '')) !== '') {
            Log::warning('facebook_oauth_user_denied', ['error' => $error, 'description' => $request->query('error_description')]);

            return redirect()->to('/admin/social/accounts');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            abort(400, 'OAuth code fehlt.');
        }

        $appId = SocialSetting::getDecrypted('facebook.app_id', '');
        $appSecret = SocialSetting::getDecrypted('facebook.app_secret', '');
        if ($appId === '' || $appSecret === '') {
            abort(400, 'Facebook App-ID / Secret fehlt.');
        }

        $redirectUri = rtrim((string) config('app.url', 'http://localhost'), '/').'/oauth/facebook/callback';

        // 1. Short-lived user token.
        $tokenResp = Http::acceptJson()->get(self::GRAPH.'/oauth/access_token', [
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);
        if (! $tokenResp->ok()) {
            Log::warning('facebook_oauth_code_exchange_failed', ['status' => $tokenResp->status(), 'body' => $tokenResp->body()]);
            abort(400, 'Facebook token exchange fehlgeschlagen.');
        }
        $shortToken = (string) ($tokenResp->json('access_token') ?? '');
        if ($shortToken === '') {
            abort(400, 'Facebook access_token fehlt.');
        }

        // 2. Exchange for long-lived user token (~60 days).
        $longResp = Http::acceptJson()->get(self::GRAPH.'/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortToken,
        ]);
        if (! $longResp->ok()) {
            Log::warning('facebook_oauth_long_exchange_failed', ['status' => $longResp->status(), 'body' => $longResp->body()]);
            // Fall back to short-lived token if long-lived exchange failed.
            $userToken = $shortToken;
            $userExpiresAt = null;
        } else {
            $userToken = (string) ($longResp->json('access_token') ?? $shortToken);
            $expiresIn = (int) ($longResp->json('expires_in') ?? 0);
            $userExpiresAt = $expiresIn > 0 ? now()->addSeconds($expiresIn) : null;
        }

        // 3. Fetch viewer profile so we have a label.
        $me = Http::withToken($userToken)->acceptJson()->get(self::GRAPH.'/me', ['fields' => 'id,name']);
        $viewerId = (string) ($me->json('id') ?? '');
        $viewerName = (string) ($me->json('name') ?? 'Facebook');
        if ($viewerId === '') {
            abort(400, 'Facebook /me fehlgeschlagen.');
        }

        SocialAccount::query()->updateOrCreate(
            ['platform' => 'facebook_user', 'external_user_id' => $viewerId],
            [
                'label' => 'FB '.$viewerName,
                'access_token' => $userToken,
                'refresh_token' => null,
                'token_expires_at' => $userExpiresAt,
                'scopes' => '',
                'meta' => ['name' => $viewerName],
            ],
        );

        // 4. Enumerate pages and persist each Page token + connected IG business account.
        $pages = Http::withToken($userToken)->acceptJson()->get(self::GRAPH.'/me/accounts', [
            'fields' => 'id,name,access_token,instagram_business_account{id,username,name}',
            'limit' => 100,
        ]);
        if ($pages->ok()) {
            foreach ((array) $pages->json('data', []) as $page) {
                $pageId = (string) ($page['id'] ?? '');
                $pageName = (string) ($page['name'] ?? '');
                $pageToken = (string) ($page['access_token'] ?? '');
                if ($pageId === '' || $pageToken === '') {
                    continue;
                }
                SocialAccount::query()->updateOrCreate(
                    ['platform' => 'facebook', 'external_user_id' => $pageId],
                    [
                        'label' => 'FB '.$pageName,
                        'access_token' => $pageToken,
                        'refresh_token' => null,
                        'token_expires_at' => null, // Page tokens inherit long-lived parent
                        'scopes' => 'pages_manage_posts,pages_read_engagement',
                        'meta' => ['page_name' => $pageName],
                    ],
                );

                $igId = (string) ($page['instagram_business_account']['id'] ?? '');
                if ($igId !== '') {
                    $igName = (string) ($page['instagram_business_account']['username'] ?? $page['instagram_business_account']['name'] ?? '');
                    SocialAccount::query()->updateOrCreate(
                        ['platform' => 'instagram', 'external_user_id' => $igId],
                        [
                            'label' => 'IG @'.$igName,
                            'access_token' => $pageToken, // IG Business uses the Page token
                            'refresh_token' => null,
                            'token_expires_at' => null,
                            'scopes' => 'instagram_content_publish,instagram_basic',
                            'meta' => [
                                'facebook_page_id' => $pageId,
                                'username' => $igName,
                            ],
                        ],
                    );
                }
            }
        } else {
            Log::warning('facebook_pages_fetch_failed', ['status' => $pages->status(), 'body' => $pages->body()]);
        }

        return redirect()->to('/admin/social/accounts');
    }
}

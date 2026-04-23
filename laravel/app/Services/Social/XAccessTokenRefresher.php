<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class XAccessTokenRefresher
{
    private const TOKEN_URL = 'https://api.x.com/2/oauth2/token';

    /**
     * Erneuert das Access-Token, wenn es abgelaufen ist oder bald abläuft (OAuth 2 mit refresh_token).
     */
    public function refreshIfNeeded(SocialAccount $account): void
    {
        if ($account->platform !== 'x') {
            return;
        }

        $refresh = $account->refresh_token;
        if (! is_string($refresh) || trim($refresh) === '') {
            return;
        }

        $expiresAt = $account->token_expires_at;
        if ($expiresAt === null) {
            return;
        }
        if ($expiresAt->gt(now()->addMinutes(2))) {
            return;
        }

        $clientId = SocialSetting::getDecrypted('x.client_id', '');
        $clientSecret = SocialSetting::getDecrypted('x.client_secret', '');
        if ($clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('X Client-ID/Secret fehlen für Token-Refresh.');
        }

        $resp = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post(self::TOKEN_URL, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh,
            ]);

        if (! $resp->successful()) {
            Log::warning('x_token_refresh_failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            throw new \RuntimeException('X token refresh fehlgeschlagen.');
        }

        /** @var array<string, mixed> $data */
        $data = $resp->json();
        $accessToken = (string) ($data['access_token'] ?? '');
        if ($accessToken === '') {
            throw new \RuntimeException('X refresh: access_token fehlt in der Antwort.');
        }

        $newRefresh = (string) ($data['refresh_token'] ?? '');
        $expiresIn = (int) ($data['expires_in'] ?? 0);
        $scope = (string) ($data['scope'] ?? '');

        $account->access_token = $accessToken;
        if ($newRefresh !== '') {
            $account->refresh_token = $newRefresh;
        }
        $account->token_expires_at = $expiresIn > 0 ? now()->addSeconds($expiresIn) : null;
        if ($scope !== '') {
            $account->scopes = $scope;
        }
        $account->save();
    }
}

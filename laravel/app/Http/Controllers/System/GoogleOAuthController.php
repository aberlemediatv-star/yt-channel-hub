<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use YtHub\Db;
use YtHub\HttpGuard;
use YtHub\OAuthState;
use YtHub\PublicHttp;
use YtHub\TokenCipher;

final class GoogleOAuthController extends Controller
{
    public function start(Request $request): RedirectResponse|Response
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();

        $status = HttpGuard::internalAuthStatusForAdminOnly();
        if ($status === 503) {
            return response(
                "Interner Token nicht konfiguriert (security.internal_token / INTERNAL_TOKEN).\n",
                503,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }
        if ($status === 403) {
            return response(
                "Forbidden — Token, Admin-Login oder Header X-Internal-Token erforderlich.\n",
                403,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        $channelId = (int) $request->query('channel_id', 0);
        if ($channelId <= 0) {
            return response(
                'Aufruf: oauth_start.php?channel_id=DB_ID_DES_KANALS&token=INTERNAL_TOKEN',
                400,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        $client = app_google_client();
        $client->setState(OAuthState::sign($channelId));

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request): Response
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();

        if (! $request->query->has('code')) {
            return response("Fehlender code-Parameter.\n", 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $stateRaw = (string) $request->query('state', '');
        $channelId = OAuthState::verifyAndParse($stateRaw);
        if ($channelId === null || $channelId <= 0) {
            return response("Ungültiger oder manipulierter state-Parameter.\n", 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $client = app_google_client();
        $token = $client->fetchAccessTokenWithAuthCode((string) $request->query('code'));
        if (isset($token['error'])) {
            $msg = (string) ($token['error_description'] ?? $token['error'] ?? 'unknown');

            return response(
                'OAuth-Fehler: '.preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $msg),
                400,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        $refresh = $token['refresh_token'] ?? null;
        if (! $refresh) {
            return response(
                "Kein refresh_token erhalten. Erneut verbinden und sicherstellen, dass access_type=offline und prompt=consent gesetzt sind.\n",
                200,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        $cipher = new TokenCipher(app_config()['security']['encryption_key'] ?? null);
        $toStore = $cipher->encrypt((string) $refresh);

        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                'SELECT id, oauth_credential_id FROM channels WHERE id = ? LIMIT 1'
            );
            $st->execute([$channelId]);
            $ch = $st->fetch();
            if (! $ch) {
                $pdo->rollBack();

                return response("Kanal nicht gefunden (ID {$channelId}).\n", 404, ['Content-Type' => 'text/plain; charset=utf-8']);
            }

            $existingOauthId = isset($ch['oauth_credential_id'])
                ? (int) $ch['oauth_credential_id']
                : 0;
            $label = 'Kanal #'.$channelId;

            if ($existingOauthId > 0) {
                $upOauth = $pdo->prepare(
                    'UPDATE oauth_credentials SET refresh_token = ?, label = ? WHERE id = ?'
                );
                $upOauth->execute([$toStore, $label, $existingOauthId]);
                $oid = $existingOauthId;
            } else {
                $ins = $pdo->prepare(
                    'INSERT INTO oauth_credentials (label, refresh_token) VALUES (?, ?)'
                );
                $ins->execute([$label, $toStore]);
                $oid = (int) $pdo->lastInsertId();
                $upCh = $pdo->prepare(
                    'UPDATE channels SET oauth_credential_id = ? WHERE id = ?'
                );
                $upCh->execute([$oid, $channelId]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('oauth_callback DB: '.$e->getMessage());

            return response("DB-Fehler beim Speichern. Details stehen im Server-Log.\n", 500, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $lines = [
            "OAuth gespeichert. Kanal-ID {$channelId} verknüpft mit oauth_credentials.id {$oid}.\n",
        ];
        if ($cipher->isEnabled()) {
            $lines[] = "Refresh-Token wurde verschlüsselt gespeichert.\n";
        }
        $lines[] = "Als Nächstes: sync_analytics (Cron/CLI) ausführen.\n";

        return response(implode('', $lines), 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}

<?php

declare(strict_types=1);

namespace YtHub;

/**
 * Signierter OAuth-2-state (Kanal-ID + HMAC) gegen CSRF; reine Zahl bleibt als Legacy erlaubt.
 */
final class OAuthState
{
    public static function sign(int $channelId): string
    {
        $sig = hash_hmac('sha256', (string) $channelId, self::secret());

        return $channelId . ':' . $sig;
    }

    public static function verifyAndParse(string $state): ?int
    {
        $state = trim($state);
        if ($state === '') {
            return null;
        }
        $parts = explode(':', $state, 2);
        if (count($parts) !== 2) {
            return null;
        }
        $id = (int) $parts[0];
        $sig = $parts[1];
        if ($id <= 0 || $sig === '') {
            return null;
        }
        $expected = hash_hmac('sha256', (string) $id, self::secret());
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        return $id;
    }

    private static function secret(): string
    {
        $cfg = app_config();
        $t = (string) ($cfg['security']['internal_token'] ?? '');
        if ($t !== '') {
            return $t;
        }
        $k = (string) ($cfg['security']['encryption_key'] ?? '');

        return $k !== '' ? $k : 'yt-hub-oauth-state';
    }
}

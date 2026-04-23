<?php

declare(strict_types=1);

namespace YtHub;

final class HttpGuard
{
    private const COOKIE_NAME = 'yt_hub_internal';
    private const SESSION_MARKER_KEY = '_yt_hub_internal_ok';

    public static function requireInternalTokenOrCli(): void
    {
        self::assertInternalAuth(false, false);
    }

    /** Sync / OAuth per HTTP: Token oder eingeloggter Admin. */
    public static function requireInternalTokenOrCliForAdmin(): void
    {
        self::assertInternalAuth(false, true);
    }

    public static function requireInternalTokenCookieOrCli(): void
    {
        self::assertInternalAuth(true, false);
    }

    /** Backend / Analytics: Token, Session-Marker oder Admin-Session. */
    public static function requireInternalTokenCookieOrCliForAdmin(): void
    {
        self::assertInternalAuth(true, true);
    }

    /**
     * Gleiche Regeln wie requireInternalTokenOrCliForAdmin(), aber ohne exit — für Laravel o. ä.
     *
     * @return int|null HTTP-Status bei Fehler (403, 503) oder null bei OK
     */
    public static function internalAuthStatusForAdminOnly(): ?int
    {
        return self::evaluateInternalAuth(false, true);
    }

    /**
     * Gleiche Regeln wie requireInternalTokenCookieOrCliForAdmin(), aber ohne exit.
     *
     * @return int|null HTTP-Status bei Fehler (403, 503) oder null bei OK
     */
    public static function internalAuthStatusForCookieOrAdmin(): ?int
    {
        return self::evaluateInternalAuth(true, true);
    }

    private static function assertInternalAuth(bool $allowCookie, bool $allowAdminSession): void
    {
        $status = self::evaluateInternalAuth($allowCookie, $allowAdminSession);
        if ($status === null) {
            return;
        }

        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        if ($status === 503) {
            echo "Interner Token nicht konfiguriert (security.internal_token / INTERNAL_TOKEN).\n";
        } else {
            echo "Forbidden — Token, Admin-Login oder Header X-Internal-Token erforderlich.\n";
        }
        exit(1);
    }

    /**
     * @return int|null null wenn OK, sonst 403 oder 503
     */
    private static function evaluateInternalAuth(bool $allowCookie, bool $allowAdminSession): ?int
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return null;
        }

        if ($allowAdminSession) {
            AdminAuth::startSession();
            if (AdminAuth::isLoggedIn()) {
                return null;
            }
        }

        $cfg = app_config();
        $expected = (string) ($cfg['security']['internal_token'] ?? '');
        if ($expected === '') {
            return 503;
        }

        // Session-bound marker (set after a previous successful token auth). No
        // cookie containing the raw token anywhere on the wire.
        if ($allowCookie) {
            if (session_status() === PHP_SESSION_NONE) {
                AdminAuth::startSession();
            }
            if (! empty($_SESSION[self::SESSION_MARKER_KEY])) {
                return null;
            }
        }

        $given = (string) ($_SERVER['HTTP_X_INTERNAL_TOKEN'] ?? $_GET['token'] ?? '');
        if ($given !== '' && hash_equals($expected, $given)) {
            if ($allowCookie) {
                if (session_status() === PHP_SESSION_NONE) {
                    AdminAuth::startSession();
                }
                $_SESSION[self::SESSION_MARKER_KEY] = true;
            }

            return null;
        }

        return 403;
    }
}

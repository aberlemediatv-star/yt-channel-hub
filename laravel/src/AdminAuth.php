<?php

declare(strict_types=1);

namespace YtHub;

final class AdminAuth
{
    private const SESSION_KEY = 'yt_hub_admin_ok';
    private const LAST_SEEN_KEY = 'yt_hub_admin_last_seen';
    private const IDLE_SECONDS = 3600; // 60 minutes of inactivity ⇒ auto-logout

    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        $secure = self::isHttps();
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('yt_hub_sid');
        session_start();
    }

    public static function isLoggedIn(): bool
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        $last = (int) ($_SESSION[self::LAST_SEEN_KEY] ?? 0);
        if ($last > 0 && (time() - $last) > self::IDLE_SECONDS) {
            // Silent idle expiry: drop session state but keep the session alive
            // so Lang/flash still work on the login page.
            unset($_SESSION[self::SESSION_KEY], $_SESSION[self::LAST_SEEN_KEY], $_SESSION['_csrf_token']);

            return false;
        }
        $_SESSION[self::LAST_SEEN_KEY] = time();

        return true;
    }

    public static function login(string $password): bool
    {
        $hash = (string) (app_config()['admin']['password_hash'] ?? '');
        if ($hash === '') {
            return false;
        }
        if (!password_verify($password, $hash)) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = true;
        $_SESSION[self::LAST_SEEN_KEY] = time();
        return true;
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }
}

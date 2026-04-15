<?php

declare(strict_types=1);

namespace YtHub;

final class AdminAuth
{
    private const SESSION_KEY = 'yt_hub_admin_ok';

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
        return !empty($_SESSION[self::SESSION_KEY]);
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

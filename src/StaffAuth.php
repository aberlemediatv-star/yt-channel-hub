<?php

declare(strict_types=1);

namespace YtHub;

/**
 * Separater Login für Mitarbeiter (nur staff/-Bereich), nicht Admin-Session.
 */
final class StaffAuth
{
    private const SESSION_KEY = 'yt_hub_staff_ok';
    private const SESSION_ID = 'yt_hub_staff_id';

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
        session_name('yt_hub_staff');
        session_start();
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION[self::SESSION_KEY]) && (int) ($_SESSION[self::SESSION_ID] ?? 0) > 0;
    }

    public static function staffId(): int
    {
        return (int) ($_SESSION[self::SESSION_ID] ?? 0);
    }

    public static function login(int $staffId): void
    {
        if ($staffId <= 0) {
            return;
        }
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = true;
        $_SESSION[self::SESSION_ID] = $staffId;
    }

    public static function verifyAndLogin(string $username, string $password): bool
    {
        $repo = new StaffRepository(Db::pdo());
        $row = $repo->findByUsername($username);
        if ($row === null) {
            return false;
        }
        $hash = (string) ($row['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            return false;
        }
        $id = (int) $row['id'];
        self::login($id);

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

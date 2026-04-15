<?php

declare(strict_types=1);

namespace YtHub;

/**
 * CSRF für Mitarbeiter-Formulare (eigene Session).
 */
final class StaffCsrf
{
    private const SESSION_KEY = '_csrf_staff_token';

    public static function token(): string
    {
        StaffAuth::startSession();
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $submitted): bool
    {
        StaffAuth::startSession();
        $expected = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($expected) || $expected === '' || !is_string($submitted) || $submitted === '') {
            return false;
        }

        return hash_equals($expected, $submitted);
    }

    public static function hiddenField(): string
    {
        $t = self::token();

        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
    }
}

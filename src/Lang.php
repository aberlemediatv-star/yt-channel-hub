<?php

declare(strict_types=1);

namespace YtHub;

/**
 * UI languages: DE, EN, ES, FR, TH — cookie yt_hub_lang, optional ?lang=, browser (Accept-Language).
 */
final class Lang
{
    public const SUPPORTED = ['de', 'en', 'es', 'fr', 'th'];

    /** Native labels for language picker (Google-style). */
    public const NATIVE_LABELS = [
        'de' => 'Deutsch',
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'th' => 'ไทย',
    ];

    private const COOKIE = 'yt_hub_lang';

    private static ?string $resolved = null;

    public static function init(): void
    {
        if (isset($_GET['lang']) && in_array($_GET['lang'], self::SUPPORTED, true)) {
            self::$resolved = $_GET['lang'];
            self::persistCookie(self::$resolved);
            self::syncSession();
            return;
        }
        $c = $_COOKIE[self::COOKIE] ?? '';
        if (in_array($c, self::SUPPORTED, true)) {
            self::$resolved = $c;
            self::syncSession();
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['lang'])
            && in_array($_SESSION['lang'], self::SUPPORTED, true)) {
            self::$resolved = $_SESSION['lang'];
            self::persistCookie(self::$resolved);
            return;
        }
        self::$resolved = self::detectFromAcceptLanguage();
        self::persistCookie(self::$resolved);
        self::syncSession();
    }

    private static function syncSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && self::$resolved !== null) {
            $_SESSION['lang'] = self::$resolved;
        }
    }

    private static function persistCookie(string $lang): void
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        setcookie(self::COOKIE, $lang, [
            'expires' => time() + 365 * 86400,
            'path' => '/',
            'secure' => $secure,
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[self::COOKIE] = $lang;
    }

    private static function detectFromAcceptLanguage(): string
    {
        $header = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        if ($header === '') {
            return 'de';
        }
        $parts = preg_split('/,\s*/', $header) ?: [];
        foreach ($parts as $part) {
            $part = trim(explode(';', $part, 2)[0]);
            if ($part === '') {
                continue;
            }
            $tag = strtolower(str_replace('_', '-', $part));
            foreach (explode('-', $tag) as $segment) {
                $segment = strtolower($segment);
                if ($segment === '') {
                    continue;
                }
                if (strlen($segment) === 2 && in_array($segment, self::SUPPORTED, true)) {
                    return $segment;
                }
                if (strlen($segment) >= 2) {
                    $two = substr($segment, 0, 2);
                    if (in_array($two, self::SUPPORTED, true)) {
                        return $two;
                    }
                }
            }
        }

        return 'de';
    }

    public static function code(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }
        $c = $_COOKIE[self::COOKIE] ?? '';
        if (in_array($c, self::SUPPORTED, true)) {
            return $c;
        }
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['lang'])
            && in_array($_SESSION['lang'], self::SUPPORTED, true)) {
            return $_SESSION['lang'];
        }

        return 'de';
    }

    /**
     * Aktuelle Script-URL mit lang-Parameter (andere GET-Parameter bleiben erhalten).
     */
    public static function urlWithLang(string $langCode): string
    {
        if (!in_array($langCode, self::SUPPORTED, true)) {
            $langCode = 'de';
        }
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $q = $_GET;
        $q['lang'] = $langCode;

        return $path . '?' . http_build_query($q);
    }

    /**
     * Relativer Link zu einer PHP-Seite im gleichen Verzeichnis mit ?lang= (aktuell oder vorgegeben).
     */
    public static function relUrl(string $scriptBasename, ?string $langCode = null): string
    {
        $lang = $langCode !== null && in_array($langCode, self::SUPPORTED, true)
            ? $langCode
            : self::code();

        return $scriptBasename . '?' . http_build_query(['lang' => $lang]);
    }

    /**
     * Absolute URL für hreflang (aktueller Host + Script-Verzeichnis).
     */
    public static function absoluteUrl(string $relativeFromPublicDir): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            ? 'https'
            : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $dir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');

        return $scheme . '://' . $host . $dir . '/' . ltrim($relativeFromPublicDir, '/');
    }

    public static function t(string $key): string
    {
        $lang = self::code();
        $messages = self::messages();
        if (isset($messages[$lang][$key])) {
            return $messages[$lang][$key];
        }

        return $messages['de'][$key] ?? $key;
    }

    /**
     * Trusted HTML/markup from translation files only (e.g. privacy policy). Not for user input.
     */
    public static function tRich(string $key): string
    {
        return self::t($key);
    }

    /** @var array<string, array<string, string>>|null */
    private static ?array $messagesCache = null;

    /**
     * @return array<string, array<string, string>>
     */
    private static function messages(): array
    {
        if (self::$messagesCache === null) {
            self::$messagesCache = [
                'de' => require __DIR__ . '/lang/messages_de.php',
                'en' => require __DIR__ . '/lang/messages_en.php',
                'es' => require __DIR__ . '/lang/messages_es.php',
                'fr' => require __DIR__ . '/lang/messages_fr.php',
                'th' => require __DIR__ . '/lang/messages_th.php',
            ];
        }

        return self::$messagesCache;
    }

}

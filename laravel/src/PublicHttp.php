<?php

declare(strict_types=1);

namespace YtHub;

final class PublicHttp
{
    private static bool $sent = false;

    /** @var string|null Nonce für CSP (JSON-LD / Inline-Skripte, die Nonce tragen) */
    private static ?string $cspNonce = null;

    /**
     * CSP-Nonce (gleicher Wert wie im Header script-src). Nach sendSecurityHeaders() nutzbar.
     */
    public static function cspNonce(): string
    {
        if (self::$cspNonce === null) {
            self::$cspNonce = bin2hex(random_bytes(16));
        }

        return self::$cspNonce;
    }

    public static function sendSecurityHeaders(): void
    {
        if (self::$sent || headers_sent()) {
            return;
        }
        self::$sent = true;
        $nonce = self::cspNonce();
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), interest-cohort=()');
        header(
            "Content-Security-Policy: default-src 'self'; " .
            "img-src 'self' https://i.ytimg.com https://*.ytimg.com data: blob:; " .
            "style-src 'self' 'unsafe-inline'; " .
            "script-src 'self' 'nonce-{$nonce}'; " .
            "font-src 'self' data:; " .
            "connect-src 'self'; " .
            "frame-ancestors 'self'; " .
            "base-uri 'self'; " .
            "form-action 'self'"
        );
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

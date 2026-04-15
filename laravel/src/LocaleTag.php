<?php

declare(strict_types=1);

namespace YtHub;

/**
 * Prüft BCP-47-ähnliche Sprach-Tags (YouTube snippet / localizations / captions).
 */
final class LocaleTag
{
    public static function isLikely(string $s): bool
    {
        $s = trim($s);

        return $s !== '' && preg_match('/^[a-zA-Z]{2,8}(-[a-zA-Z0-9]{1,8})*$/', $s) === 1;
    }
}
